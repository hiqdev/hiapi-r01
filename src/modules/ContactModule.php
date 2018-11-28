<?php

namespace hiapi\r01\modules;

use format;

class ContactModule extends AbstractModule
{
    public function contactSet(array $row): array
    {
        if ($this->contactExists($row)) {
            $result = $this->contactUpdate($row);
        } else {
            $result = $this->contactCreate($row);
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
            'nic_hdl'   => $this->nic_hdl($row['epp_id'], $row['type'] == 'org')
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreate(array $row): array
    {
        return $this->handleContact($row, 'add');
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactUpdate(array $row): array
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
        return $this->tool->request($op . 'DadminPerson', array(
            'nic_hdl'       => $this->nic_hdl($row['epp_id']),
            'fiorus'        => $this->fiorus($row['name']),
            'fioeng'        => $this->fioeng($row['name']),
            'passport'      => format::passport($row),
            'birth_date'    => format::date($row['birth_date'],'ru'),
            'postal_addr'   => format::address($row),
            'phone'         => format::e123($row['voice_phone']),
            'fax'           => format::e123($row['fax_phone'] ? : $row['voice_phone']),
            'e_mail'        => $row['email'],
            'isprotected'   => 0,
            'isresident'    => 0,
        ), [
            'id' => 'nic_hdl'
        ]);
    }
}
