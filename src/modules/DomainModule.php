<?php

namespace hiapi\r01\modules;

use arr;
use err;
use format;

class DomainModule extends AbstractModule
{
    /**
     * @param string $domain
     * @return array
     */
    private function domainCheck(string $domain): array
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

        $result = $this->tool->request('addDomain', [
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

        return err::is($result) ? $result : array_merge($row, $result);
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
    public function domainInfo ($row): array
    {
        return $this->domainGetInfo($row);
    }

    /**
     * @param $rows
     * @return array
     */
    public function domainsGetInfo ($rows): array
    {
        $res = [];
        foreach ($rows as $row) {
            $info = $this->domainGetInfo($row);
            $res[$row['domain']] = err::is($info) ? null : $info;
        };
        return $res;
    }

    /**
     * @param $row
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

    /**
     * @param $row
     * @return array
     */
    public function domainSetNSs($row): array
    {
        $row['nss'] = $this->_prepareNSs($row);
        $dd = $this->domainInfo($row);
        if ($dd['nameservers'] == arr::cjoin($row['nss'])) {
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
    public function _prepareNSs($row): array
    {
        $domain = $row['domain'];
        foreach (arr::csplit($row['nss']) as $host) {
            if (substr($host, -strlen($domain)) == $domain) {
                $my_nss[$host] = $host;
            } else {
                $nss[$host] = $host;
            }
        };
        $nss = [];
        if (isset($my_nss)) {
            $his = $this->base->hostsGetInfo(arr::make_sub($my_nss, 'host'));
            if (err::is($his)) {
                return $his;
            }
            foreach ($his as $k => $v) {
                $nss[$v['host']] = "$v[host] $v[ip]";
            }
        };
        return $nss;
    }

    /**
     * @param $row
     * @return array
     */
    public function domainUpdate($row): array
    {
        $row = $this->_domainPrepareData($row);
        if (err::is($row)) {
            return $row;
        }
        return $this->tool->request('updateDomain', [
            'domain'           => $row['domain'],
            'nservers'         => arr::cjoin($row['nss'], "\n"),
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
     * @param $row
     * @return mixed
     */
    public function _domainPrepareData($row): array
    {
        $cinfo = $this->base->domainGetContactsInfo(arr::mget($row, 'domain,id'));
        if (err::is($cinfo)) {
            return $cinfo;
        }
        $dataSet = arr::has($row, 'whois_protected') ? $row : $cinfo;
        $row['whois_protected'] = arr::get($dataSet, 'whois_protected') ? 1 : 0;
        $epp_id = $row['registrant'];
        if (!$epp_id || !$this->tool->contactExists(compact('epp_id'))) {
            $contact = $this->tool->contactSet(arr::get($cinfo, 'registrant'));
            if (err::is($contact)) {
                return $contact;
            }
            $row['registrant'] = $contact['id'];
        } else {
            $row['registrant'] = $this->nic_hdl($epp_id);
        }
        return $row;
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
     * @param array $jrow
     * @return array
     */
    public function domainsLoadInfo (array $jrow): array
    {
        return $jrow;
    }

    /**
     * @param array $row
     * @return bool
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
     */
    public function domainsEnableLock ($rows)
    {
        return error('unsupported for this zone');
    }

    /**
     * @param $rows
     * @return null
     */
    public function domainsDisableLock ($rows)
    {
        return error('unsupported for this zone');
    }


}
