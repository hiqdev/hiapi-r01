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
}
