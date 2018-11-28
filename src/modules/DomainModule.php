<?php

namespace hiapi\r01\modules;

use arr;
use format;

class DomainModule extends AbstractModule
{
    /**
     * @param string $domain
     * @return mixed
     */
    private function domainCheck(string $domain)
    {
        return $this->tool->request('checkDomainAvailable', [
            'domain_name' => $domain,
        ], [
            'available' => 'available',
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
            $result[$domain] = $this->domainCheck($domain)['available'];
        }

        return $result;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainRegister(array $row): array
    {
        if (!$row['nss']) {
            $row['nss'] = $this->base->domainGetNSs($row)['nss'] ?? null;
        }
        if (!$row['nss']) {
            $row['nss'] = $this->tool->getDefaultNss();
        }
        $row = $this->domainPrepareContacts($row);

        return $this->tool->request('addDomain', [
            'domain'           => $row['domain'],
            'nservers'         => arr::cjoin($row['nss'], "\n"),
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
    public function domainInfo($row)
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
        foreach (arr::csplit($dd->nserver, "\n") as $ns) {
            $nss[] = explode(' ', $ns)[0];
        }
        return [
            'domain'          => strtolower($dd['name']),
            'nameservers'     => arr::cjoin($nss),
            'registrant'      => $dd->{'admin-o'},
            'created_date'    => format::datetime($dd['created'], 'iso'),
            'expiration_date' => format::date($dd['reg-till'], 'iso'),
        ];
    }

}
