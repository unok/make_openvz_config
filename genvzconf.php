<?php

/**
 * Class LimitValue
 *
 * @property int $soft_limit
 * @property int $hard_limit
 */
class LimitValue
{
    public $soft_limit;
    public $hard_limit;

    public function set($s, $h)
    {
        if ($s == null || $h == null)
        {
            throw new Exception("Set undefined value");
        }
        $this->soft_limit = $s;
        $this->hard_limit = $h;
    }
}

/**
 * Class Value
 *
 * @property string $value
 */
class Value
{
    public $value;

    public function set($v)
    {
        if ($v == null || $v === '')
        {
            throw new Exception("Set undefined value");
        }
        $this->value = $v;
    }
}

/**
 * Class VZConfTemplate
 *
 * @property LimitValue $kmemsize
 * @property LimitValue $lockedpages
 * @property LimitValue $privvmpages
 * @property LimitValue $shmpages
 * @property LimitValue $numproc
 * @property LimitValue $physpages
 * @property LimitValue $vmguarpages
 * @property LimitValue $oomguarpages
 * @property LimitValue $numtcpsock
 * @property LimitValue $numflock
 * @property LimitValue $numpty
 * @property LimitValue $numsiginfo
 * @property LimitValue $tcpsndbuf
 * @property LimitValue $tcprcvbuf
 * @property LimitValue $othersockbuf
 * @property LimitValue $dgramrcvbuf
 * @property LimitValue $numothersock
 * @property LimitValue $dcachesize
 * @property LimitValue $numfile
 * @property LimitValue $avnumproc
 * @property LimitValue $numiptent
 *
 * @property LimitValue $diskspace
 * @property LimitValue $diskinodes
 *
 * @property Value $onboot
 * @property Value $quotatime
 * @property Value $cpuunits
 *
 * @property Value $ve_root
 * @property Value $ve_private
 * @property Value $ostemplate
 * @property Value $origin_sample
 * @property Value $ip_address
 * @property Value $hostname
 * @property Value $nameserver
 * @property Value $searchdomain
 */
class VZConf
{
    public $kmemsize;
    public $lockedpages;
    public $privvmpages;
    public $shmpages;
    public $numproc;
    public $physpages;
    public $vmguarpages;
    public $oomguarpages;
    public $numtcpsock;
    public $numflock;
    public $numpty;
    public $numsiginfo;
    public $tcpsndbuf;
    public $tcprcvbuf;
    public $othersockbuf;
    public $dgramrcvbuf;
    public $numothersock;
    public $dcachesize;
    public $numfile;
    public $avnumproc;
    public $numiptent;

    public $diskspace;
    public $diskinodes;

    public $onboot;
    public $quotatime;
    public $cpuunits;
    public $ve_root;
    public $ve_private;
    public $ostemplate;
    public $origin_sample;
    public $ip_address;
    public $hostname;
    public $nameserver;
    public $searchdomain;

    public function __construct()
    {
        $this->kmemsize     = new LimitValue();
        $this->lockedpages  = new LimitValue();
        $this->privvmpages  = new LimitValue();
        $this->shmpages     = new LimitValue();
        $this->numproc      = new LimitValue();
        $this->physpages    = new LimitValue();
        $this->vmguarpages  = new LimitValue();
        $this->oomguarpages = new LimitValue();
        $this->numtcpsock   = new LimitValue();
        $this->numflock     = new LimitValue();
        $this->numpty       = new LimitValue();
        $this->numsiginfo   = new LimitValue();
        $this->tcpsndbuf    = new LimitValue();
        $this->tcprcvbuf    = new LimitValue();
        $this->othersockbuf = new LimitValue();
        $this->dgramrcvbuf  = new LimitValue();
        $this->numothersock = new LimitValue();
        $this->dcachesize   = new LimitValue();
        $this->numfile      = new LimitValue();
        $this->avnumproc    = new LimitValue();
        $this->numiptent    = new LimitValue();

        $this->diskspace  = new LimitValue();
        $this->diskinodes = new LimitValue();

        $this->onboot = new Value();

        $this->quotatime = new Value();

        $this->cpuunits = new Value();

        $this->ve_root       = new Value();
        $this->ve_private    = new Value();
        $this->ostemplate    = new Value();
        $this->origin_sample = new Value();
        $this->ip_address    = new Value();
        $this->hostname      = new Value();
        $this->nameserver    = new Value();
        $this->searchdomain  = new Value();
    }

    /**
     * @return string
     */
    public function makeConfContents()
    {
        $unset_list = array();
        $contents   = '';
        foreach (get_class_vars(get_class()) as $key => $name)
        {
            if ($this->$key instanceof LimitValue)
            {
                if ($this->$key->hard_limit != null && $this->$key->soft_limit != null)
                {
                    $contents .= sprintf("%s=\"%d:%d\"\n", strtoupper($key), $this->$key->soft_limit, $this->$key->hard_limit);
                }
                else
                {
                    $unset_list[] = $key;
                }
            }
            elseif ($this->$key instanceof Value)
            {
                if ($this->$key->value != null)
                {
                    $contents .= sprintf("%s=\"%s\"\n", strtoupper($key), preg_replace("/\"/", "\\\"", $this->$key->value));
                }
                else
                {
                    $unset_list[] = $key;
                }
            }
        }
        if (count($unset_list) > 0)
        {
            printf("\t[WARN] UNSET VARS: %s\n", join(',', $unset_list));
        }
        return $contents;
    }
}

class MachineManager
{
    const SOFT_TO_HARD_LIMIT_RATE = 1.2;
    const CONF_DIR                = './conf';


    /**
     * @var VZConf
     */
    public $vzconf;

    /**
     * @var string
     */
    private $machine_ip;

    /**
     * @var int
     */
    private $vps_id;


    private $user_id;

    private $user_password;

    /**
     * @var Resource
     */
    private $connection;

    /**
     * @param $machine_ip
     * @param $vps_id
     */
    public function __construct($machine_ip, $vps_id)
    {
        $this->machine_ip = $machine_ip;
        $this->vps_id     = $vps_id;

        $this->vzconf = new VZConf();

        $this->user_id       = 'root';
        $this->user_password = 'hogehoge';

        $this->vzconf->onboot->set('yes');
        $this->vzconf->ve_root->set('/vz/root/$VEID');
        $this->vzconf->ve_private->set('/vz/private/$VEID');
        $this->vzconf->ostemplate->set('centos-6-x86-devel');
        $this->vzconf->origin_sample->set('pve.auto');
        $this->vzconf->quotatime->set('0');
        $this->vzconf->cpuunits->set('1000');
    }

    /**
     * @param $user_id
     * @param $password
     */
    public function setUser($user_id, $password)
    {
        $this->user_id       = $user_id;
        $this->user_password = $password;
    }

    public function research()
    {
        $this->connection = $this->getConnection();
        $disk_size        = $this->getDiskSize();
        $inode_size       = $this->getInodeSize();

        $search_domain = $this->getSearchDomain();
        $name_server   = $this->getNameServer();
        $hostname      = $this->getHostName();
        $ip_address    = $this->getIpAddress();

        if ($this->canGetUserBeancounters())
        {
            $this->readGetUserBeancounters();
        }


        $this->vzconf->diskspace->set($disk_size, $this->getHardLimit($disk_size));
        $this->vzconf->diskinodes->set($inode_size, $this->getHardLimit($inode_size));

        if ($search_domain !== '')
        {
            $this->vzconf->searchdomain->set($search_domain);
        }
        $this->vzconf->nameserver->set($name_server);
        $this->vzconf->hostname->set($hostname);
        $this->vzconf->ip_address->set($ip_address);

        file_put_contents(sprintf("%s/%s.conf", MachineManager::CONF_DIR, $this->vps_id), $this->vzconf->makeConfContents());
    }

    /**
     * @return resource
     * @throws ErrorException
     */
    private function getConnection()
    {
        $connection = ssh2_connect($this->machine_ip);
        if (!ssh2_auth_password($connection, 'root', 'hogehoge'))
        {
            printf(__LINE__ . "\n");
            throw new ErrorException("cannot connect");
        }
        return $connection;
    }

    /**
     * @return int
     */
    private function getDiskSize()
    {
        return $this->getDfSize();
    }

    /**
     * @return int
     */
    private function getInodeSize()
    {
        return $this->getDfSize('-i');
    }

    /**
     * @param string $option
     * @return int
     */
    private function getDfSize($option = '')
    {
        $output = $this->execCommand('df ' . $option . ' /');
        $cells  = preg_split("/  */", preg_replace("/.*\n(.*)\n/", "$1", $output));
        return $cells[1] + 0;
    }

    /**
     * @param $soft_limit
     * @return int
     */
    private function getHardLimit($soft_limit)
    {
        return ceil($soft_limit * MachineManager::SOFT_TO_HARD_LIMIT_RATE);
    }

    /**
     * @return string
     */
    private function getSearchDomain()
    {
        $output = $this->execCommand('cat /etc/resolv.conf');
        foreach (preg_split("/\n/", $output) as $line)
        {
            if (preg_match("/^search  *(.*?) *$/", $line, $m))
            {
                return $m[1];
            }
        }
        return '';
    }

    /**
     * @return string
     */
    private function getNameServer()
    {
        $output = $this->execCommand('cat /etc/resolv.conf');
        foreach (preg_split("/\n/", $output) as $line)
        {
            if (preg_match("/^nameserver  *(.*?) *$/", $line, $m))
            {
                return $m[1];
            }
        }
        return '';
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private function getHostName()
    {
        $output = $this->execCommand("cat /etc/sysconfig/network");
        foreach (preg_split("/\n/", $output) as $line)
        {
            if (preg_match("/HOSTNAME=\"(.+)\"/", $line, $m))
            {
                return $m[1];
            }
        }
        throw new Exception("Not found hostname");
    }

    /**
     * @return string
     */
    private function getIpAddress()
    {
        return $this->machine_ip;
    }

    /**
     * @return bool
     */
    private function canGetUserBeancounters()
    {
        try
        {
            $output = $this->execCommand("cat /proc/user_beancounters");
            return true;
        } catch (Exception $e)
        {
            return false;
        }
    }

    private function readGetUserBeancounters()
    {
        $output = $this->execCommand("cat /proc/user_beancounters");
        foreach (preg_split("/\n/", $output) as $line)
        {
            $cells = preg_split("/ +/", preg_replace("/^ +([0-9]+:|uid)? +/", "", $line));
            if (count($cells) > 4)
            {
                $key = $cells[0];
                $s   = $cells[3];
                $h   = $cells[4];

                if (in_array($key, array('resource', 'dummy')))
                {
                    continue;
                }
                if (isset($this->vzconf->$key) && $this->vzconf->$key instanceof LimitValue)
                {
                    $this->vzconf->$key->set($s, $h);
                }
                else
                {
                    var_dump($key);
                }
            }
        }
    }

    /**
     * @param $command
     * @return string
     * @throws Exception
     */
    private function execCommand($command)
    {
        $stream      = ssh2_exec($this->connection, $command);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

        stream_set_blocking($errorStream, true);
        stream_set_blocking($stream, true);

        $error = stream_get_contents($errorStream);
        if (strlen($error) > 0)
        {
            throw new Exception("Cannot exec command: $command");
        }

        return stream_get_contents($stream);
    }
}


/*
 *  Main process
 */
$machine_list = array(
    1000 => "192.168.1.1",
);

foreach ($machine_list as $vps_id => $ip)
{
    echo $vps_id . "\n";
    $manager = new MachineManager($ip, $vps_id);
    $manager->research();
}
