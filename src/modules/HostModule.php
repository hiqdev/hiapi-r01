<?php

namespace hiapi\r01\modules;

use Exception;

class HostModule extends AbstractModule
{
    public function hostCreate(array $row)
    {
        throw new Exception('unsupported operation');
    }

    public function hostsDelete(array $row)
    {
        throw new Exception('unsupported operation');
    }

    /**
     * @param $row
     * @return array
     */
    public function hostSet($row): array
    {
        $host = $row['host'];
        $domain = substr($host, strpos($host, '.') + 1);

        $info = $this->tool->domainInfo([
            'domain' => $domain,
        ]);

        foreach (explode(",", $info['nameservers']) as $ns) {
            $nss[$ns] = $ns;
        }
        $tmp_nss = array_merge($nss, [
            $host => $host,
        ]);

        if (count($tmp_nss) < 2) {
            $tmp_nss['ns1.topdns.me'] = 'ns1.topdns.me';
        }

        $res = $this->tool->domainUpdate([
            'domain'     => $domain,
            'nss'        => $tmp_nss,
        ]);

        if (isset($res['_error'])) {
            return $res;
        }

        return $this->tool->domainUpdate([
            'domain'     => $domain,
            'nss'        => $nss,
        ]);
    }

}
