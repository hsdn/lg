## HSDN PHP Looking Glass
PHP Version of BGP Looking Glass script, based on the Perl sources: https://github.com/Cougar/lg

### General Features
- Supports the Telnet and SSH (through Putty/plink or sshpass).
- Supports the Cisco, MikroTik v5/v6, Juniper, Huawei (Comware), Quagga (Zebra) and OpenBGPD routers.
- Supports the IPv4 and IPv6 protocols.
- Automatic conversion IPs to subnets using Merit RADb for MikroTik (http://www.ra.net/).
- Drawing graph of BGP AS pathes using GraphViz toolkit.
- Works on php 5.2.0 and above.

### System Requirements
- php version 5.2.0 and above with Sockets and Filter (http://www.php.net/).
- For the SSH connections require **sshpass** or Putty **plink** command (http://www.chiark.greenend.org.uk/~sgtatham/putty/download.html).
- GraphViz toolkit for drawing BGP pathes graph (http://www.graphviz.org/).
- php pear package Image_GraphViz (http://pear.php.net/package/Image_GraphViz).

### Installation Instructions
1. Copy **index.php** file to your web server home directory.
2. Copy **lg_config.php.example** file as **lg_config.php** into your web server home directory.
3. Edit **lg_config.php** configuration file (see parameters description below).

### Configuration Parameters
#### Branding Configuration
- `$_CONFIG['asn']` - Your AS number for display on the LG page.
- `$_CONFIG['company']` - Your company for display on the LG page.
- `$_CONFIG['logo']` - Your company logo for display on the LG page.
- `$_CONFIG['color']` - Main color of design elements on the LG page.
- `$_CONFIG['ipwhois']` - URL address of the IP whois service.
- `$_CONFIG['aswhois']` - URL address of the AS whois service.

#### Programs Configuration
- `$_CONFIG['sshcommand']` - Type of command to make a SSH connection (you can use *plink* or *sshpass* as value).
- `$_CONFIG['plink']` - Path to **plink** command if you use it (for SSH connections).
- `$_CONFIG['sshpass']` - Path to **sshpass** command if you use it (for SSH connections).

#### Routers Configuration
Configuration of routers is specified as array in the parameter `$_CONFIG['routers']` with following format:
```php
$_CONFIG['routers'] = array
(
    'router1' = array
    (
        // Router parameters
    ),
    'router2' = array
    (
        // Router parameters
    ),
    // etc.
);
```

##### Router parameters:
- `url` - URL address in format: **[ssh|telnet]://[login]:[password]@[host]:[port]**.
- `pingtraceurl` - URL address for ping and traceroute tools for Quagga routers (or *FALSE*).
- `description` - Router description.
- `group` - Router group name - AS number (or *FALSE*).
- `ipv6` - Router is supports IPv6 (*TRUE* or *FALSE*).
- `os` - Router type (*ios*, *mikrotik*, *quagga,* *junos*, *openbgpd*, *huawei*).

### Demonstration
- http://dev.hsdn.org/lg/
- http://lg.apc.sg/
- http://lg.campus-rv.net/
- http://lg.lankabell.com/

#### Graph of BGP AS pathes demonstration
- http://dev.hsdn.org/lg/?command=graph&protocol=ipv4&query=8.8.8.8&router=example1
- http://lg.as51326.net/?command=graph&protocol=ipv4&query=8.8.8.8&router=example1

### License
    HSDN Looking Glass

    Copyright (C) 2012-2019 Information Networks Ltd.
    Copyright (C) 2000-2002 Cougar
    Copyright (C) 2014 Regional Networks Ltd.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
