<?php

namespace hiapi\r01\modules;

use Exception;

class DomainModule extends AbstractModule
{
    /**
     * @param string $domain
     * @return array
     */
    private function domainCheck(array $row): array
    {
        return $this->tool->request('checkDomainAvailable', [
            'domain_name' => $row['domain'],
        ], [
            'avail' => 'available',
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainsCheck(array $row): array
    {
        $result = [];
        foreach ($row['domains'] as $domain) {
            $result = $this->domainCheck([
                'domain' => $domain,
            ]);
        }

        return $result;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainRegister(array $row): array
    {
        $row = $this->domainPrepareDataForCreate($row);

        $result = $this->tool->request('addDomain', [
            'domain'           => $row['domain'],
            'nservers'         => implode("\n", $row['nss']),
            'admin_o'          => $row['registrant_remote_id'],
            'descr'            => '',
            'check_whois'      => 0,
            'hide_name_nichdl' => $row['whois_protected'],
            'hide_email'       => $row['whois_protected'],
            'spam_process'     => 1,
            'hide_phone'       => $row['whois_protected'],
            'hide_phone_email' => $row['whois_protected'],
            'years'            => $row['period'],
            'registrar'        => '',
            'dont_test_ns'     => 1,
        ], [
            'id' => 'taskid',
        ]);

        return array_merge($row, $result);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainInfo ($row): array
    {
        return $this->domainGetInfo($row);
    }

    /**
     * @param array $rows
     * @return array
     */
    public function domainsGetInfo ($rows): array
    {
        $res = [];
        foreach ($rows as $row) {
            $res[$row['domain']] = $this->tool->domainInfo($row);
        }

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainGetInfo($row): array
    {
        $res = $this->tool->request('getDomains', [
            'params'     => [
                'domain' => $row['domain'],
            ],
            'strict'     => 1,
            'sort_field' => 'domain',
            'sort_dir'   => 'asc',
            'limit'      => 10,
            'page'       => 1,
        ]);

        $dd = $res['data']['domainarray'][0];

        if (empty($dd['name'])) {
            throw new Exception('Object does not exist');
        }

        foreach (explode("\n", $dd['nserver']) as $ns) {
            $nss[] = explode(' ', $ns)[0];
            [$ns, $ips] = explode(' ', $ns, 2);
            $nss[$ns] = $ns;
        }

        return [
            'domain'          => strtolower($dd['name']),
            'nameservers'     => implplode(",", $nss),
            'registrant'      => $dd['admin-o'],
            'created_date'    => date("Y-m-d H:i:s", strtotime($dd['created'])),
            'expiration_date' => date("Y-m-d H:i:s", strtotime($dd['reg-till'])),
        ];
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainRenew(array $row): array
    {
        return $this->tool->request('prolongDomain', [
            'domain' => $row['domain'],
            'years'  => $row['period'],
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainPrepareContacts(array $row): array
    {
        $contacts = $this->base->domainGetContactsInfo($row);
        if (!$contacts) {
            return $contacts;
        }
        $remoteIds = [];
        foreach ($this->base->getContactTypes() as $type) {
            $contactId = $contacts[$type]['id'];
            $remoteId = $remoteIds[$contactId];
            if (!$remoteId) {
                $response = $this->tool->contactSet($contacts[$type]);
                $remoteId = $response['epp_id'];
                $remoteIds[$contactId] = $remoteId;
            }
            $row[$type . '_remote_id'] = $remoteId;
        }

        return $row;
    }


    /**
     * @param $row
     * @return array
     */
    public function domainSetNSs($row): array
    {
        $row['nss'] = $this->_prepareNSs($row);
        $dd = $this->tool->domainInfo($row);
        if ($dd['nameservers'] == implode("\n", $row['nss'])) {
            return $row;
        }

        return $this->domainUpdate(array_merge($row, [
            'registrant' => $dd['registrant'],
        ]));
    }

    /**
     * @param $row
     * @return array
     */
    public function domainUpdate($row): array
    {
        $row = $this->domainPrepareDataForUpdate($row);

        return $this->tool->request('updateDomain', [
            'domain'           => $row['domain'],
            'nservers'         => implode("\n", $row['nss']),
            'admin_o'          => $row['registrant'],
            'descr'            => '',
            'need_replace'     => 1,
            'hide_name_nichdl' => $row['whois_protected'],
            'hide_email'       => $row['whois_protected'],
            'spam_process'     => 1,
            'hide_phone_email' => $row['whois_protected'],
            'dont_test_ns'     => 1,
        ]);
    }

    /**
     * @param array $jrow
     * @return array
     */
    public function domainsLoadInfo (array $jrow): array
    {
        return $jrow;
    }

    /**
     * @param array $rows
     * @return array
     */
    public function domainsSaveContacts(array $rows)
    {
        return $rows;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainSaveContacts (array $row): array
    {
        return $row;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainSetPassword (array $row): array
    {
        return $row;
    }

    /**
     * @param $rows
     * @return null
     * @thow new Exception
     */
    public function domainsEnableLock ($rows)
    {
        throw new Exception('unsupported for this zone');
    }

    /**
     * @param $rows
     * @return null
     * @thow new Exception
     */
    public function domainsDisableLock ($rows)
    {
        throw new Exception('unsupported for this zone');
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainPrepareDataForUpdate(array $row) : array
    {
        $info = $this->tool->domainInfo($row);
        $row['registrant'] = $info['registrant'];
        $row['whois_protected'] = $row['whois_protected'] ? 1 : 0;
        $row['nss'] = $this->domainPrepareNSs($row);

        return $row;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainPrepareDataForCreate(array $row) : array
    {
        if (!$row['nss']) {
            $row['nss'] = $this->base->domainGetNSs($row)['nss'] ?? null;
        }

        if (!$row['nss']) {
            $row['nss'] = $this->tool->getDefaultNss();
        }

        foreach ($this->tool->getContactTypes() as $type) {
            $contactId = $contacts["{$type}_info"]['id'];
            $remoteId = $remoteIds[$contactId];
            if (!$remoteId) {
                $response = $this->tool->contactSet($row["{$type}_info"]);
                $remoteId = $response['epp_id'];
                $remoteIds[$contactId] = $remoteId;
            }
            $row[$type . '_remote_id'] = $remoteId;
        }

        $row['nss'] = $this->domainPrepareNSs($row);
        $row['whois_protected'] = $row['whois_protected'] ? 1 : 0;

        return $row;
    }

    /**
     * @param $row
     * @return array
     */
    public function domainPrepareNSs($row): array
    {
        $domain = $row['domain'];
        foreach (explode(",", $row['nss']) as $host) {
            if (substr($host, -strlen($domain)) == $domain) {
                $my_nss[$host] = $host;
            } else {
                $nss[$host] = $host;
            }
        }

        $nss = [];
        if (isset($my_nss)) {
            foreach ($my_nss as $k => $v) {
                $data = $this->base->hostGetInfo(['host' => $v]);
                $nss[$v] = "$data[host] $data[ip]";
            }
        }

        return $nss;
    }
}
