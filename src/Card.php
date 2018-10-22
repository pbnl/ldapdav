<?php
namespace DsvSu\LdapDav;

use Sabre\DAV;
use Sabre\CardDAV;
use Sabre\VObject\Component\VCard;

class Card extends DAV\File implements CardDAV\ICard
{
    private $name;
    private $data;

    function __construct($entry)
    {
        $this->name = $entry->get('uid')[0] . '.vcf';

        $tel = $entry->get('telephonenumber')[0];
        $shortTel = substr($tel, -4);

        $vcard = new VCard(
            [
                'FN'  => $entry->get('cn')[0] .  " " . $entry->get('sn')[0],
                'N'   => [ $entry->get('sn')[0],
                            $entry->get('cn')[0] ],
                "NICKNAME" => $entry->get('uid')[0],
            ]
        );
        isset($entry->get("mail")[0]) ? $vcard->add("EMAIL", $entry->get("mail")[0]) : null;
        isset($entry->get("telephonenumber")[0]) ? $vcard->add("TEL", $entry->get("telephonenumber")[0],["type"=>"MAIN"]) : null;
        isset($entry->get("mobile")[0]) ? $vcard->add("TEL", $entry->get("mobile")[0],["type"=>"CELL"]) : null;
        $vcard->add("ADR", [null, null, $entry->get("street")[0], null, null, $entry->get("postalcode")[0], $entry->get("l")[0]],["type"=>"HOME"]);

        $this->data = $vcard->serialize();
    }

    function getName()
    {
        return $this->name;
    }

    function get()
    {
        return $this->data;
    }

    function getContentType()
    {
        return 'text/vcard; charset=utf-8';
    }

    function getSize()
    {
        return strlen($this->data);
    }
}
