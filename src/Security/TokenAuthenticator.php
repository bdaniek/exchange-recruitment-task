<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepositoryInterface;
use App\Repository\UserTokenRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserTokenRepositoryInterface $userTokenRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $tokenValue = substr($request->headers->get('Authorization', ''), 7);

        $userToken = $this->userTokenRepository->findByToken($tokenValue);

        if (null === $userToken || !$userToken->isValid()) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired token.');
        }

        $userId = $userToken->getUserId();

        return new SelfValidatingPassport(
            new UserBadge((string) $userId, function () use ($userId): User {
                $user = $this->userRepository->findById($userId);

                if (null === $user) {
                    throw new UserNotFoundException();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['error' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
