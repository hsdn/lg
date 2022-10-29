<?php
// ------------------------------------------------------------------------
// LOOKING GLASS CONFIGURATIONS
// ------------------------------------------------------------------------

/*
 * Your own AS number
 */
$_CONFIG['asn'] = '265138';

/*
 * Your own company name
 */
$_CONFIG['company'] = 'ACESSO.COM';

/*
 * Your logo URL (or FALSE)
 */
$_CONFIG['logo'] = 'lg_logo.gif';

/*
 * Main style color
 */
$_CONFIG['color'] = '#E48559';

/*
 * SSH authentication type (`password` or `privatekey`)
 */
$_CONFIG['sshauthtype'] = 'password';

/*
 * Absolute path to SSH private key
 */
$_CONFIG['sshprivatekeypath'] = '/opt/lg/keys/id_rsa';

/*
 * Type of command for SSH connection with password authentication (`plink` or `sshpass`)
 */
$_CONFIG['sshpwdcommand'] = 'sshpass';

/*
 * Putty `plink' command path (for SSH connections)
 */
$_CONFIG['plink'] = '/usr/local/bin/plink';

/*
 * sshpass command path (for SSH connections)
 */
$_CONFIG['sshpass'] = '/usr/bin/sshpass';

/*
 * sshpass command path (for SSH connections)
 */
$_CONFIG['ssh'] = '/usr/bin/sshpass';

/*
 * URL address of the IP whois service
 */
$_CONFIG['ipwhois'] = 'http://noc.hsdn.org/whois/';

/*
 * URL address of the AS whois service
 */
$_CONFIG['aswhois'] = 'http://noc.hsdn.org/aswhois/';

/**
 * Router nodes
 *
 * Parameters:
 *      url               - URL address in format: [ssh|telnet]://[login]:[password]@[host]:[port]
 *      sshauthtype       - SSH authentication type in format: [password|privatekey]
 *      sshprivatekeypath - SSH private key path (only works if sshauthtype = privatekey) in example format: [/opt/lg/keys/id_rsa]. Optional. If excluded, value from $_CONFIG will be used.
 *      pingtraceurl      - URL address for ping and traceroute tools (or FALSE)
 *      description       - Node description
 *      group             - Node group name (of FALSE)
 *      ipv6              - Node supports IPv6 (TRUE/FALSE)
 *      os                - Node OS (ios, mikrotik, quagga, junos)
 */
$_CONFIG['routers'] = array
(
        'example1' => array
        (
                'url' => 'ssh://"suporte":"Senha@Super131377"@10.61.61.2:1822',
                'pingtraceurl' => FALSE,
                'description' => 'NE40',
                'group' => 'BGP',
                'ipv6' => TRUE,
                'os' => 'huawei',
        ),

        'example2' => array
        (
                'url' => 'ssh://ilunne:iyukiinna@10.2.60.2:2222',
                'pingtraceurl' => FALSE,
                'description' => 'CHR LG',
                'group' => 'BGP',
                'ipv6' => TRUE,
                'os' => 'mikrotik',
        )
);


/* End of file */
?>