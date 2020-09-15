<?php

namespace hiapi\r01\modules;

use err;
use format;

class ContactModule extends AbstractModule
{
    public function contactSet(array $row): array
    {
        if ($this->contactExists($row)) {
            $result = $this->contactUpdatePerson($row);
        } else {
            $result = $this->contactCreatePerson($row);
        }

        return $result;
    }

    /**
     * @param array $row
     * @return bool
     */
    public function contactExists(array $row): bool
    {
        return $this->_contactExists($row)['exist'] === 1;
    }

    /**
     * @param array $row
     * @return array
     */
    public function _contactExists(array $row): array
    {
        return $this->tool->request('checkDadminExists', [
            'nic_hdl' => $this->nic_hdl($row['epp_id'], $row['type'] == 'org'),
        ], [
            'exist' => 'exist',
        ]);
    }

    /**
     * @param $row
     * @return array
     */
    public function contactCreate(array $row): array
    {
        return $this->contactSet($row);
    }

    /**
     * @param $row
     * @return array
     */
    public function contactUpdate(array $row): array
    {
        return $this->contactSet($row);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactSave (array $row): array
    {
        return $this->contactSet($row);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreatePerson(array $row): array
    {
        return $this->handleContact($row, 'add');
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactUpdatePerson(array $row): array
    {
        return $this->handleContact($row, 'update');
    }

    /**
     * @param array $row
     * @param string $op
     * @return array
     */
    protected function handleContact(array $row, string $op): array
    {
        $result = $this->tool->request($op . 'DadminPerson', [
            'nic_hdl'     => $this->nic_hdl($row['epp_id']),
            'fiorus'      => $this->fiorus($row['name']),
            'fioeng'      => $this->fioeng($row['name']),
            'passport'    => format::passport($row),
            'birth_date'  => format::date($row['birth_date'], 'ru'),
            'postal_addr' => format::address($row),
            'phone'       => format::e123($row['voice_phone']),
            'fax'         => format::e123($row['fax_phone'] ?: $row['voice_phone']),
            'e_mail'      => $row['email'],
            'isprotected' => 0,
            'isresident'  => 0,
        ], [
            'id' => 'nic_hdl',
        ]);

        return array_merge($result, $row);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreateOrg(array $row): array
    {
        return $this->handleContactOrg($row, 'add');
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactUpdateOrg(array $row): array
    {
        return $this->handleContactOrg($row, 'update');
    }

    /**
     * @param array $row
     * @param string $op
     * @return array
     */
    public function handleContactOrg(array $row, string $op): array
    {
        $result = $this->tool->request($op . 'DadminOrg', [
            'nic_hdl'       => $this->nic_hdl($row['epp_id'], true),
            'orgname_ru'    => $row['organization_ru'],
            'orgname_en'    => $row['organization'],
            'inn'           => $row['inn'],
            'kpp'           => $row['kpp'],
            'ogrn'          => null,
            'legal_addr'    => format::address($row),
            'postal_addr'   => format::address($row),
            'phone'         => format::e123($row['voice_phone']),
            'fax'           => format::e123($row['fax_phone'] ?: $row['voice_phone']),
            'e_mail'        => $row['email'],
            'director_name' => $this->fiorus($row['director_name']),
            'bank'          => null,
            'ras_schet'     => null,
            'kor_schet'     => null,
            'bik'           => null,
            'isresident'    => ($row['isresident'] ? 1 : 0),
        ], [
            'id' => 'nic_hdl',
        ]);

        return array_merge($result, $row);;
    }

}
