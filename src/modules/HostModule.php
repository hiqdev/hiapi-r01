<?php

namespace hiapi\r01\modules;

use arr;
use err;

class HostModule extends AbstractModule
{
    public function hostCreate(array $row)
    {
        return error('unsupported operation');
    }

    public function hostsDelete(array $row)
    {
        return error('unsupported operation');
    }

    /**
     * @param $row
     * @return array
     */
    public function hostSet($row): array
    {
        $host = $row['host'];
        $domain = substr($host, strpos($host, '.') + 1);
        $registrant = $this->tool->_domainGetRegistrant(compact('domain'));
        $nss = $this->tool->_prepareNSs([
            'domain' => $domain,
            'nss'    => arr::get($this->base->domainGetNSs(compact('domain')), 'nss'),
        ]);
        $tmp_nss = $nss;
        $tmp_nss[$host] = $host . ' ' . (reset($row['ips']) ?: $row['ip']);
        if (count($tmp_nss) < 2) {
            $tmp_nss['ns1.topdns.me'] = 'ns1.topdns.me';
        }
        $res = $this->tool->domainUpdate([
            'domain'     => $domain,
            'nss'        => $tmp_nss,
            'registrant' => $registrant,
        ]);

        if (err::is($res)) {
            return $res;
        }
        return $this->tool->domainUpdate([
            'domain'     => $domain,
            'nss'        => $nss,
            'registrant' => $registrant,
        ]);
    }

}
