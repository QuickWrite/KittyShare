<?php

namespace KittyShare\Manager;

use KittyShare\Repository\{SessionRepository, UserRepository};
use KittyShare\Http\Session as HttpSession;
use KittyShare\Model\Session;

/**
 * Manages authentication and authenticated user sessions.
 *
 * Responsible for authenticating users against the user repository,
 * creating and tracking authenticated sessions, and terminating sessions
 * when users log out or when their sessions expire.
 */
final class AuthenticationManager
{
    /**
     * The key used to store the application session identifier
     * in the PHP session.
     */
    private const SESSION_KEY = 'auth.session_id';

    /**
     * Creates a new authentication manager.
     *
     * @param UserRepository    $userRepository    Repository used to retrieve users and verify credentials.
     * @param SessionRepository $sessionRepository Repository used to create, retrieve, and delete sessions.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SessionRepository $sessionRepository,
    ) {
    }

    /**
     * Authenticates a user and creates an authenticated session.
     *
     * The supplied credentials are checked against the user repository.
     * If the credentials are valid, the current PHP session ID is regenerated
     * and a new application session is created and stored in the PHP session.
     *
     * @param string $username The username to authenticate.
     * @param string $password The plaintext password to verify.
     *
     * @return Session|null The newly created session, or null if the credentials are invalid.
     */
    public function login(string $username, string $password): ?Session
    {
        $user = $this->userRepository->getUser($username);

        if ($user === null) {
            return null;
        }

        if (!password_verify($password, $user->passwordHash)) {
            return null;
        }

        HttpSession::regenerate();

        $session = $this->sessionRepository->create($user);

        HttpSession::set(self::SESSION_KEY, $session->id);

        return $session;
    }

    /**
     * Retrieves the currently authenticated session.
     *
     * The session identifier is read from the PHP session and resolved
     * through the session repository. Sessions that do not exist or have
     * expired are treated as unauthenticated and their stored identifier
     * is removed from the PHP session.
     *
     * @return Session|null The current authenticated session, or null if no valid session exists.
     */
    public function currentSession(): ?Session
    {
        $id = HttpSession::get(self::SESSION_KEY);

        if ($id === null) {
            return null;
        }

        $session = $this->sessionRepository->find($id);

        if ($session === null || $session->isExpired()) {
            HttpSession::remove(self::SESSION_KEY);

            return null;
        }

        return $session;
    }

    /**
     * Logs out the currently authenticated user.
     *
     * If an application session exists, it is deleted from the session
     * repository. The corresponding session identifier is then removed
     * from the PHP session regardless of whether the application session
     * could be found.
     *
     * @return void
     */
    public function logout(): void
    {
        $id = HttpSession::get(self::SESSION_KEY);

        if ($id !== null) {
            $session = $this->sessionRepository->find($id);

            if ($session !== null) {
                $this->sessionRepository->delete($session);
            }
        }

        HttpSession::remove(self::SESSION_KEY);
    }
}

