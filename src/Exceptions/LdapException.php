<?php

namespace Core\Exceptions;

use Exception;

class LdapException extends Exception
{
    /**
     * Create a new LDAP exception instance.
     *
     * @param resource|string $ldap The LDAP link identifier.
     * @param Exception $previous The previous exception.
     */
    public function __construct($ldap, Exception $previous = null)
    {
       if (is_resource($ldap)) {
           parent::__construct(ldap_error($ldap), ldap_errno($ldap), $previous);
       }
       else {
           parent::__construct($ldap, 0, $previous);
       }
    }
}
