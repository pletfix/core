<?php

namespace Core\Services;

use Core\Services\Contracts\Flash as FlashContract;
use Core\Services\Contracts\Session;

/**
 * The Session Object is an adapter of the [PHP Session](http://php.net/manual/en/session.examples.basic.php).
 */
class Flash implements FlashContract
{
    /**
     * Session
     *
     * @var Session
     */
    protected $session;

    /**
     * Get the session.
     *
     * @return Session
     */
    private function session()
    {
        if ($this->session === null) {
            $this->session = DI::getInstance()->get('session');
        }

        return $this->session;
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return $this->session()->has('_flash.current.' . $key);
    }

    /**
     * @inheritdoc
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->session()->get('_flash.current', $default);
        }

        return $this->session()->get('_flash.current.' . $key, $default);
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value)
    {
        if ($key === null) {
            $this->session()->set('_flash.next', $value);
        }
        else {
            $this->session()->set('_flash.next.' . $key, $value);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function merge($key, array $values)
    {
        $session = $this->session();

        if ($key === null) {
            foreach ($values as $key => $value) {
                $session->set('_flash.next.' . $key, $value);
            }
        }
        else {
            $session->set('_flash.next.' . $key, array_merge($session->get('_flash.next.' . $key, []), $values));
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setNow($key, $value)
    {
        if ($key === null) {
            $this->session()->set('_flash.current', $value);
        }
        else {
            $this->session()->set('_flash.current.' . $key, $value);
        }

        return $this;
    }


    /**
     * @inheritdoc
     */
    public function delete($key)
    {
        $this->session()->delete('_flash.current.' . $key);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->session()->delete('_flash.current');

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function reflash(array $keys = null)
    {
        $session = $this->session();
        if ($keys === null) {
            $session->set('_flash.next', $session->get('_flash.current'));
        }
        else {
            foreach ($keys as $key) {
                $session->set('_flash.next.' . $key, $session->get('_flash.current.' . $key));
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function age()
    {
        $session = $this->session();
        $session->set('_flash.current', $session->get('_flash.next'));
        $session->delete('_flash.next');

        return $this;
    }
}
