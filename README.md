## HSDN PHP Looking Glass
PHP Version of BGP Looking Glass script, based on the Perl sources: https://github.com/Cougar/lg

### General Features:
- Supports the Telnet and SSH (through Putty/plink)
- Supports the Cisco, MikroTik v5 and v6, Quagga (Zebra) and JunOS routers
- Supports the IPv4 and IPv6 protocols
- Automatic conversion IPs to subnets using Merit RADb (for MikroTik) (http://www.ra.net/)
- Drawing graph of BGP AS pathes using GraphViz toolkit
- Works on php 5.2.0 and above

### System Requirements:
- php version 5.2.0 and above with Sockets and Filter (http://www.php.net/)
- Putty for SSH connections usign `plink' command (http://www.chiark.greenend.org.uk/~sgtatham/putty/download.html)
- GraphViz toolkit for drawing BGP pathes graph (http://www.graphviz.org/)
- php pear package Image_GraphViz (http://pear.php.net/package/Image_GraphViz)

### General demonstration
- http://lg.hsdn.org/
- http://lg.regnets.ru/

### Graph of BGP AS pathes demonstration
- http://lg.hsdn.org/?command=graph&protocol=ipv6&query=google.com&router=saratov-br1

### License
    HSDN Looking Glass

    Copyright (C) 2012-2014 Information Networks Ltd.
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
