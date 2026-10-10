<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserRepositoryInterface;
use App\Repository\UserTokenRepositoryInterface;
use App\Security\TokenAuthenticator;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

#[AllowMockObjectsWithoutExpectations]
class TokenAuthenticatorTest extends TestCase
{
    private UserTokenRepositoryInterface $userTokenRepository;
    private UserRepositoryInterface $userRepository;

    private TokenAuthenticator $tokenAuthenticator;

    protected function setUp(): void
    {
        $this->userTokenRepository = $this->createMock(UserTokenRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->tokenAuthenticator = new TokenAuthenticator($this->userTokenRepository, $this->userRepository);
    }

    public function testSupportsSuccessfully(): void
    {
        $request = new Request(
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer token',
            ]
        );

        $result = $this->tokenAuthenticator->supports($request);

        $this->assertTrue($result);
    }

    public function testSupportsMissingHeader(): void
    {
        $request = new Request();

        $result = $this->tokenAuthenticator->supports($request);

        $this->assertFalse($result);
    }

    public function testSupportsInvalidHeaderConstruction(): void
    {
        $request = new Request(
            server: [
                'HTTP_AUTHORIZATION' => 'Basic token',
            ]
        );

        $result = $this->tokenAuthenticator->supports($request);

        $this->assertFalse($result);
    }

    public function testOnAuthenticationSuccessAlwaysReturnsNull(): void
    {
        $result = $this->tokenAuthenticator->onAuthenticationSuccess(
            $this->createStub(Request::class),
            $this->createStub(TokenInterface::class),
            'main'
        );

        self::assertNull($result);
    }

    public function testAuthenticateThrowsWhenTokenNotFound(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->userTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn(null);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer some-token']);

        $this->tokenAuthenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenTokenExpired(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);

        $expiredToken = new UserToken(
            id: 1,
            userId: 42,
            token: 'expired-token',
            expiresAt: new DateTimeImmutable('-1 hour'),
            createdAt: new DateTimeImmutable('-2 hours'),
        );

        $this->userTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($expiredToken);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer expired-token']);

        $this->tokenAuthenticator->authenticate($request);
    }

    public function testAuthenticateReturnsPassportWithUserLoader(): void
    {
        $userToken = new UserToken(
            id: 1,
            userId: 42,
            token: 'valid-token',
            expiresAt: new DateTimeImmutable('+1 hour'),
            createdAt: new DateTimeImmutable(),
        );

        $user = new User(
            id: 42,
            email: 'test@example.com',
            roles: ['ROLE_USER'],
            createdAt: new DateTimeImmutable(),
        );

        $this->userTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->with('valid-token')
            ->willReturn($userToken);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($user);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);

        $passport = $this->tokenAuthenticator->authenticate($request);

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        $badge = $passport->getBadge(UserBadge::class);
        self::assertSame('42', $badge->getUserIdentifier());
        self::assertSame($user, ($badge->getUserLoader())());
    }

    public function testAuthenticateUserLoaderThrowsWhenUserNotFound(): void
    {
        $userToken = new UserToken(
            id: 1,
            userId: 42,
            token: 'valid-token',
            expiresAt: new DateTimeImmutable('+1 hour'),
            createdAt: new DateTimeImmutable(),
        );

        $this->userTokenRepository
            ->expects($this->once())
            ->method('findByToken')
            ->willReturn($userToken);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);

        $passport = $this->tokenAuthenticator->authenticate($request);
        $badge = $passport->getBadge(UserBadge::class);

        $this->expectException(UserNotFoundException::class);
        ($badge->getUserLoader())();
    }

    public function testOnAuthenticationFailureReturnsErrorMessage(): void
    {
        $exception = new CustomUserMessageAuthenticationException('Invalid or expired token.');

        $response = $this->tokenAuthenticator->onAuthenticationFailure(new Request(), $exception);

        $body = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Invalid or expired token.', $body['error']);
    }

    public function testOnAuthenticationFailureInterpolatesMessageData(): void
    {
        $exception = new CustomUserMessageAuthenticationException('Token "{{ token }}" is invalid.', ['{{ token }}' => 'abc123']);

        $response = $this->tokenAuthenticator->onAuthenticationFailure(new Request(), $exception);

        $body = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Token "abc123" is invalid.', $body['error']);
    }
}
