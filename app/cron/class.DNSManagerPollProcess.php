<?php

class DNSManagerPollProcess implements \Scalr\System\Pcntl\ProcessInterface
{
    public $ThreadArgs;
    public $ProcessDescription = "DNS zone manager process (poll method)";
    public $Logger;
    private $authInfo;
    public $IsDaemon;

    public function __construct()
    {
        // Get Logger instance
        $this->Logger = Logger::getLogger(__CLASS__);
    }

    public function OnStartForking()
    {
        $db = \Scalr::getDb();

        if (!\Scalr::config('scalr.dns.global.enabled'))
            $this->ThreadArgs = array();
        else {
            $this->ThreadArgs = $db->GetAll("SELECT dns_zones.id FROM dns_zones INNER JOIN clients ON clients.id = dns_zones.client_id WHERE dns_zones.status NOT IN(?,?) OR (isonnsserver='1' AND dns_zones.status=?) ORDER BY `priority` DESC LIMIT 0,100", array(
                DNS_ZONE_STATUS::ACTIVE,
                DNS_ZONE_STATUS::INACTIVE,
                DNS_ZONE_STATUS::INACTIVE
            ));
        }
    }

    public function OnEndForking()
    {
        if (!\Scalr::config('scalr.dns.global.enabled'))
            return true;

        $db = \Scalr::getDb();

        $remoteBind = new Scalr_Net_Dns_Bind_RemoteBind();

        $transport = new Scalr_Net_Dns_Bind_Transports_LocalFs('/usr/sbin/rndc', '/var/named/etc/namedb/client_zones');

        $remoteBind->setTransport($transport);

        $zones = $db->GetAll("SELECT id FROM dns_zones WHERE iszoneconfigmodified = '1'");

        $s_zones = array();
        if (count($zones) != 0) {
            foreach ($zones as $zone) {
                $DBDNSZone = DBDNSZone::loadById($zone['id']);

                switch ($DBDNSZone->status) {
                    case DNS_ZONE_STATUS::PENDING_DELETE:
                    case DNS_ZONE_STATUS::INACTIVE:
                        $remoteBind->removeZoneFromNamedConf($DBDNSZone->zoneName);
                        break;

                    default:
                        $remoteBind->addZoneToNamedConf($DBDNSZone->zoneName, $DBDNSZone->getContents(true));
                        $DBDNSZone->status = DNS_ZONE_STATUS::ACTIVE;
                        break;
                }
                $s_zones[] = $DBDNSZone;
            }

            $remoteBind->saveNamedConf();

            foreach ($s_zones as $DBDNSZone) {
                if ($DBDNSZone->status == DNS_ZONE_STATUS::PENDING_DELETE) {
                    $DBDNSZone->remove();
                } else {
                    if ($DBDNSZone->status == DNS_ZONE_STATUS::INACTIVE) {
                        $DBDNSZone->isOnNsServer = 0;
                    } else {
                        $DBDNSZone->isOnNsServer = 1;
                    }
                    $DBDNSZone->isZoneConfigModified = 0;
                    $DBDNSZone->save();
                }
            }
        }
        $remoteBind->reloadBind();
    }

    public function StartThread($zone)
    {
        $DBDNSZone = DBDNSZone::loadById($zone['id']);

        $remoteBind = new Scalr_Net_Dns_Bind_RemoteBind();

        $transport = new Scalr_Net_Dns_Bind_Transports_LocalFs('/usr/sbin/rndc', '/var/named/etc/namedb/client_zones');
           $remoteBind->setTransport($transport);

           switch($DBDNSZone->status)
           {
               case DNS_ZONE_STATUS::PENDING_DELETE:
               case DNS_ZONE_STATUS::INACTIVE:

                   $remoteBind->removeZoneDbFile($DBDNSZone->zoneName);

                   $DBDNSZone->isZoneConfigModified = 1;

                   break;

               case DNS_ZONE_STATUS::PENDING_CREATE:
               case DNS_ZONE_STATUS::PENDING_UPDATE:

                   $remoteBind->addZoneDbFile($DBDNSZone->zoneName, $DBDNSZone->getContents());

                   if ($DBDNSZone->status == DNS_ZONE_STATUS::PENDING_CREATE)
                       $DBDNSZone->isZoneConfigModified = 1;

                   $DBDNSZone->status = DNS_ZONE_STATUS::ACTIVE;

                   break;
           }

           $DBDNSZone->save();
        exit();
    }
}
