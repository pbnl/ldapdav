<?php
namespace DsvSu\LdapDav;

use Sabre\DAV;
use Sabre\CardDAV;
use Toyota\Component\Ldap;

class AddressBook extends DAV\Collection implements CardDAV\IAddressBook, DAV\IMultiGet
{
    private static $ATTRS = [
        'uid', 'cn', 'givenName', 'sn', 'telephoneNumber', 'mail', 'mobile', 'street', 'postalCode', 'l'
    ];
    /** @var Ldap\Core\Manager */
    private $ldap;
    /** @var Card[]|null */
    private $children;
    
    public function __construct($base_dn)
    {
        $this->ldap = new Ldap\Core\Manager(
            [
                'hostname' => 'pbnl.de',
                'base_dn'  => $base_dn,
                'security' => 'SSL'
            ],
            new Ldap\Platform\Native\Driver
        );
        $this->ldap->connect();
        $this->ldap->bind(getenv("ldap_bind_dn"), getenv("ldap_bind_password"));
    }

    public function getName()
    {
        return 'pbnl';
    }

    public function getChildren()
    {
        if (!isset($this->children)) {
            $entries = $this->ldap->search(
                null,
                '(givenName=*)',
                true,
                self::$ATTRS
            );

            $this->children = [];
            foreach ($entries as $entry) {
                $this->children[$entry->get('uid')[0] . '.vcf']
                        = new Card($entry);
            }
        }
        return $this->children;
    }

    public function getMultipleChildren(array $paths)
    {
        $children = $this->getChildren();
        $ret = [];
        foreach ($paths as $path) {
            if (isset($children[$path])) {
                $ret[] = $children[$path];
            }
        }
        return $ret;
    }
    
    public function getChild($name)
    {
        static $calledBefore = false;

        // If function is called multiple times we might as well
        // fetch all entries to speed up
        if ($calledBefore) {
            $children = $this->getChildren();
            if (isset($children[$name])) {
                return $children[$name];
            } else {
                throw new DAV\Exception\NotFound('Not Found');
            }
        }

        $calledBefore = true;

        if (!preg_match('/^([.a-z0-9-]+)\.vcf$/', $name, $matches)) {
            throw new DAV\Exception\NotFound('Not Found');
        }

        $entries = $this->ldap->search(
            null,
            "(uid=$matches[1])",
            true,
            self::$ATTRS
        );

        if ($entries->current()) {
            return new Card($entries->current());
        } else {
            throw new DAV\Exception\NotFound('Not Found');
        }
    }
}
