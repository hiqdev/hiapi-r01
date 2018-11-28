<?php

namespace hiapi\r01\modules;

class DomainModule extends AbstractModule
{
    /**
     * @param string $domain
     * @return mixed
     */
    private function domainCheck(string $domain)
    {
        return $this->tool->request('checkDomainAvailable', [
            'domain_name' => $domain
        ])['available'];
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainsCheck(array $row): array
    {
        $result = [];
        foreach ($row['domains'] as $domain) {
            $result[$domain] = $this->domainCheck($domain);
        }

        return $result;
    }

    public function domainRegister(array $row): array
    {
        if (!$row['nss']) {
            $row['nss'] = $this->base->domainGetNSs($row)['nss'] ?? null;
        }
        if (!$row['nss']) {
            $row['nss'] = $this->tool->getDefaultNss();
        }
        $row = $this->domainPrepareContacts($row);
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
}
