<?php
/**
 * HSDN PHP Looking Glass version 1.2.22b
 *
 * General Features:
 *  - Supports the Telnet and SSH (through Putty/plink or sshpass)
 *  - Supports the Cisco, MikroTik v5/v6, Juniper, Huawei (Comware), 
 *       Quagga (Zebra) and OpenBGPD routers.
 *  - Supports the IPv4 and IPv6 protocols
 *  - Automatic conversion IPs to subnets using RADb (for MikroTik)
 *  - Drawing graph of BGP AS pathes using GraphViz toolkit
 *  - Works on php 5.2.0 and above
 *
 * System Requirements:
 *  - php version 5.2.0 and above with Sockets and Filter
 *      http://www.php.net/
 *  - Putty for SSH connections usign `plink' command
 *      http://www.chiark.greenend.org.uk/~sgtatham/putty/download.html
 *  - GraphViz toolkit for drawing BGP pathes graph
 *      http://www.graphviz.org/
 *  - php pear package Image_GraphViz 
 *      http://pear.php.net/package/Image_GraphViz
 *
 *
 * Copyright (C) 2012-2018 Information Networks Ltd. <info@hsdn.org>
 *                         http://www.hsdn.org/
 *
 * Copyright (C) 2000-2002 Cougar <cougar@random.ee>
 *                         http://www.version6.net/
 *
 * Copyright (C) 2014 Regional Networks Ltd. <info@regnets.ru>
 *                         http://www.regnets.ru/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//error_reporting(0);

// ------------------------------------------------------------------------
// Do not edit below this line, unless you fully understand the implications.
// ------------------------------------------------------------------------

// Configurations defaults. DO NOT EDIT THIS!
// For your configurations, please use the file `lg_config.php'
$_CONFIG = array
(
	'asn' => '12345',
	'company' => 'My Company Name',
	'logo' => 'lg_logo.gif',
	'color' => '#E48559',
	'sshcommand' => 'plink',
	'plink' => '/usr/local/bin/plink',
	'sshpass' => '/usr/bin/sshpass',
	'ipwhois' => 'http://noc.hsdn.org/whois/',
	'aswhois' => 'http://noc.hsdn.org/aswhois/',
	'routers' => array(),
);

@ob_end_flush();

if (file_exists('lg_config.php') AND is_readable('lg_config.php'))
{
	require_once 'lg_config.php';
}

$router = isset($_REQUEST['router']) ? trim($_REQUEST['router']) : FALSE; 
$protocol = isset($_REQUEST['protocol']) ? trim($_REQUEST['protocol']) : FALSE;
$command = isset($_REQUEST['command']) ? trim($_REQUEST['command']) : FALSE;
$query = isset($_REQUEST['query']) ? trim($_REQUEST['query']) : FALSE;

if ($command != 'graph' OR !isset($_REQUEST['render']) OR !isset($_CONFIG['routers'][$router]))
{
// HTML header
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<!--
			=================================================
			Powered by HSDN PHP Looking Glass
			- https://git.dev.hsdn.org/pub/lg
			- https://github.com/hsdn/lg
			=================================================
		-->
		<title>AS<?php print $_CONFIG['asn'] ?> Looking Glass</title>
		<meta charset="utf-8">
		<link rel="shortcut icon" href="favicon.ico">
		<style type="text/css"> 
		<!--
			body { font: 14px normal Arial, Helvetica, sans-serif; margin: 30px 10%; color: #000; background: #fff; }
			h2 { font-size: 24px; font-weight: normal; }
			form { margin: 0; padding: 0 0 15px 0; }
			p, object { margin: 0; padding: 0 0 15px 0; }
			hr { margin: 0 0 15px 0; border: none; color: #000; background-color: #000; height: 1px; }
			a:link, a:visited { color: <?php print $_CONFIG['color'] ?>; }
			a:hover { color: #ccc; }
			table { border: 0; }
			table th { background: <?php print $_CONFIG['color'] ?>; color: #fff; white-space: nowrap; font-size: 14px; text-align: center; }
			.form { margin: auto; text-align: left; background: #efefef; border: 5px solid #efefef; }
			.center { text-align: center; }
			.error { color: red; font-weight: bold; }
			.warning { color: blue; font-weight: bold; }
			.legend { font-size: 12px; margin: auto; }
		//-->
		</style>
		<script type="text/javascript">
		<!--
			function load() {
				var loading = document.getElementById('loading');
				if (loading !== null) {
					loading.style.display = 'none';
				}
			}
		//-->
		</script>
	</head>
	<body onload="load();">
<?php if (isset($_CONFIG['logo']) AND $_CONFIG['logo']): ?>
		<div class="center"><a href="?"><img src="<?php print $_CONFIG['logo'] ?>" border="0" alt="lg"></a></div>
<?php endif ?>
		<div class="center"><h2>AS<?php print $_CONFIG['asn'] ?> Looking Glass</h2></div>
		<hr>
<?php
flush();
}

$queries = array
(
	'ios' => array
	(
		'ipv4' => array
		(
			'bgp' => 'show ip bgp %s',
			'advertised-routes' => 'show ip bgp neighbors %s advertised-routes',
			'received-routes' => 'show ip bgp neighbors %s received-routes',
			'routes' =>	'show ip bgp neighbors %s routes',
			'summary' => 'show ip bgp summary',
			'ping' =>	'ping ip %s',
			'trace' =>	'traceroute ip %s'
		),
		'ipv6' => array
		(
			'bgp' => 'show bgp ipv6 unicast %s',
			'advertised-routes' => 'show bgp ipv6 neighbors %s advertised-routes',
			'received-routes' => 'show bgp ipv6 neighbors %s received-routes',
			'routes' =>	'show bgp ipv6 neighbors %s routes',
			'summary' => 'show bgp ipv6 unicast summary',
			'ping' => 'ping ipv6 %s',
			'trace' => 'traceroute ipv6 %s'
		)
	),
	'quagga' => array
	(
		'ipv4' => array
		(
			'bgp' => 'show ip bgp %s',
			'advertised-routes' => 'show ip bgp neighbors %s advertised-routes',
			'received-routes' => 'show ip bgp neighbors %s received-routes',
			'routes' => 'show ip bgp neighbors %s routes',
			'summary' => 'show ip bgp summary',
			'ping' => 'ping -t 5 -c 5 %s',
			'trace' => 'traceroute -Iaw 1 %s',
		),
		'ipv6' => array
		(
			'bgp' => 'show ipv6 bgp %s',
			'advertised-routes' => 'show ipv6 bgp neighbors %s advertised-routes',
			'received-routes' => 'show ipv6 bgp neighbors %s received-routes',
			'routes' => 'show ipv6 bgp neighbors %s routes',
			'summary' => 'show ipv6 bgp summary',
			'ping' => 'ping6 -c 5 %s',
			'trace' => 'traceroute6 -Ialw 1 %s',
		)
	),
	'mikrotik' => array
	(
		'ipv4' => array
		(
			'bgp' => '/ip route print detail where bgp dst-address=%s',
			'advertised-routes' => '/routing bgp advertisements print peer=%s',
			'routes' => '/ip route print where gateway=%s',
			'summary' => '/routing bgp peer print status where address-families=ip',
			'ping' => '/ping count=5 size=56 %s',
			'trace' => '/tool traceroute %s size=60 count=1',
		),
		'ipv6' => array
		(
			'bgp' => '/ipv6 route print detail where bgp dst-address=%s',
			'advertised-routes' => '/routing bgp advertisements print peer=%s',
			'routes' => '/ipv6 route print where gateway=%s',
			'summary' => '/routing bgp peer print status where address-families=ipv6',
			'ping' => '/ping count=5 size=56 %s',
			'trace' => '/tool traceroute %s size=60 count=1',
		)
	),
	'junos' => array
	(
		'ipv4' => array
		(
			'bgp' => 'show bgp %s',
			'advertised-routes' => 'show route advertising-protocol bgp %s',
			'routes'	=> 'show route receive-protocol bgp %s active-path',
			'summary' => 'show bgp summary',
			'ping' => 'ping count 5 %s',
			'trace' => 'traceroute %s as-number-lookup',
		),
		'ipv6' => array
		(
			'bgp' => 'show bgp %s',
			'advertised-routes' => 'show route advertising-protocol bgp %s',
			'routes'	=> 'show route receive-protocol bgp %s active-path',
			'summary' => 'show bgp summary',
			'ping' => 'ping count 5 %s',
			'trace' => 'traceroute %s',
		)
	),
	'openbgpd' => array
	(
		'ipv4' => array
		(
			'bgp' => 'bgpctl show ip bgp %s',
			'advertised-routes'	=> 'bgpctl show rib neighbor %s out',
			'received-routes' => 'bgpctl show rib neighbor %s in',
			'routes'	=> 'bgpctl show rib selected neighbor %s',
			'summary' => 'bgpctl show summary',
			'ping' => 'ping -c 5 %s',
			'trace' => 'traceroute %s',
		),
		'ipv6' => array
		(
			'bgp' => 'show bgp %s',
			'advertised-routes' => 'bgpctl show rib neighbor %s out',
			'received-routes' => 'bgpctl show rib neighbor %s in',
			'routes'	=> 'bgpctl show rib selected neighbor %s',
			'summary' => 'bgpctl show summary',
			'ping' => 'ping6 -c 5 %s',
			'trace' => 'traceroute6 %s',
		)
	),
	'huawei' => array
	(
		'ipv4' => array
		(
			'bgp' => 'display bgp routing-table %s',
			'advertised-routes'	=> 'display bgp routing-table peer %s advertised-routes',
			'received-routes' => 'display bgp routing-table peer %s received-routes',
			'routes'	=> 'display bgp routing-table peer %s received-routes active',
			'summary' => 'display bgp peer',
			'ping' => 'ping %s',
			'trace' => 'tracert %s',
		),
		'ipv6' => array
		(
			'bgp' => 'display bgp ipv6 routing-table %s',
			'advertised-routes' => 'display bgp ipv6 routing-table peer %s advertised-routes',
			'received-routes' => 'display bgp ipv6 routing-table peer %s received-routes',
			'routes'	=> 'display bgp ipv6 routing-table peer %s received-routes active',
			'summary' => 'display bgp ipv6 peer',
			'ping' => 'ping ipv6 %s',
			'trace' => 'tracert ipv6 %s',
		)
	),
);

if (isset($_CONFIG['routers'][$router]) AND 
	isset($queries[$_CONFIG['routers'][$router]['os']][$protocol]) AND
	(isset($queries[$_CONFIG['routers'][$router]['os']][$protocol][$command]) OR $command == 'graph'))
{
	if ($protocol == 'ipv6' AND (!isset($_CONFIG['routers'][$router]['ipv6']) OR 
		$_CONFIG['routers'][$router]['ipv6'] !== TRUE))
	{
		$protocol = 'ipv4';

		print '<div class="center"><p class="warning">The router does not support IPv6. Using IPv4.</p></div>';
		print '<hr>';
	}

	$url = $_CONFIG['routers'][$router]['url'];

	if (($command == 'ping' OR $command == 'trace') AND 
		isset($_CONFIG['routers'][$router]['pingtraceurl']) AND 
		$_CONFIG['routers'][$router]['pingtraceurl'] != FALSE)
	{
		$url = $_CONFIG['routers'][$router]['pingtraceurl'];
	}

	$url = @parse_url($url);

	$os = $_CONFIG['routers'][$router]['os'];

	if ($command == 'graph' AND isset($queries[$os][$protocol]['bgp']))
	{
		$exec = $queries[$os][$protocol]['bgp'];
	}
	else
	{
		$exec = $queries[$os][$protocol][$command];
	}

	if (strpos($exec, '%s') !== FALSE)
	{
		if (preg_match('/^.[.a-z0-9_-]+\.[a-z]+$/i', $query))
		{
			if ($command != 'advertised-routes')
			{
				$query = get_host($query);
			}
		}

		if ($query AND ($command == 'bgp' OR $command == 'graph') AND ($os == 'mikrotik' OR ($protocol == 'ipv6' AND $os == 'ios')))
		{
			if (strpos($query, '/') === FALSE AND $radb = get_radb($query))
			{
				$route = FALSE;

				if (strpos($query, ':') AND isset($radb['route6']))
				{
					$route = $radb['route6'];
				}
				else if (isset($radb['route']))
				{
					$route = $radb['route'];
				}

				if ($route)
				{
					if ($command != 'graph')
					{
						print '<p>Address <b>'.$query.'</b> is converted to a subnet <b>'.$route.'</b> using the <a href="http://radb.net/" target="_blank">Merit RADb</a></p>';
					}

					$query = $route;
				}
			}
		}

		$exec = sprintf($exec, escapeshellcmd($query));

		if (!$query)
		{
			$exec = FALSE;

			if ($query === FALSE)
			{
				print '<div class="center"><p class="error">Can\'t resolve the hostname.</p></div>';
			}
			else
			{
				print '<div class="center"><p class="error">Parameter missing.</p></div>';
			}
		}
	}
	else if ($query != '' AND $command != 'graph')
	{
		print '<div class="center"><p class="warning">No parameter needed.</p></div>';
		print '<hr>';
	}

	if ($exec)
	{
		if ($os == 'junos') 
		{
			// @see JunOS Routing Table Names (http://www.net-gyver.com/?p=602)
			$table = ($protocol == 'ipv6') ? 'inet6.0' : 'inet.0';

			if (preg_match("/^show bgp n\w*\s+([\d\.A-Fa-f:]+)$/", $exec, $exec_exp))
			{
				$exec = 'show bgp neighbor '.$exec_exp[1];
			}
			else if (preg_match("/^show bgp n\w*\s+([\d\.A-Fa-f:]+) ro\w*$/", $exec, $exec_exp)) 
			{
				$exec = 'show route receive-protocol bgp '.$exec_exp[1];
			}
			else if (preg_match("/^show bgp neighbors ([\d\.A-Fa-f:]+) routes all$/", $exec, $exec_exp))
			{
				$exec = 'show route receive-protocol bgp '.$exec_exp[1].' all';
			}
			else if (preg_match("/^show bgp neighbors ([\d\.A-Fa-f:]+) routes damping suppressed$/", $exec, $exec_exp)) 
			{
				$exec = 'show route receive-protocol bgp '.$exec_exp[1].' damping suppressed';
			}
			else if (preg_match("/^show bgp n\w*\s+([\d\.A-Fa-f:]+) advertised-routes ([\d\.A-Fa-f:\/]+)$/", $exec, $exec_exp))
			{
				$exec = 'show route advertising-protocol bgp '.$exec_exp[1].' '.$exec_exp[2].' exact detail';
			}
			else if (preg_match("/^show bgp n\w*\s+([\d\.A-Fa-f:]+) receive-protocol ([\d\.A-Fa-f:\/]+)$/", $exec, $exec_exp)) 
			{
				$exec = 'show route receive-protocol bgp '.$exec_exp[1].' '.$exec_exp[2].' exact detail';
			}
			else if (preg_match("/^show bgp n\w*\s+([\d\.A-Fa-f:]+) a[\w\-]*$/", $exec, $exec_exp))
			{
				$exec = 'show route advertising-protocol bgp '.$exec_exp[1];
			}
			else if (preg_match("/^show bgp\s+([\d\.A-Fa-f:]+\/\d+)$/", $exec, $exec_exp))
			{
				$exec = 'show route protocol bgp '.$exec_exp[1].' terse exact';
			}
			else if (preg_match("/^show bgp\s+([\d\.A-Fa-f:]+)$/", $exec, $exec_exp))
			{
				$exec = 'show route protocol bgp '.$exec_exp[1].' table '.$table;
			}
			else if (preg_match("/^show bgp\s+([\d\.A-Fa-f:\/]+) exact$/", $exec, $exec_exp)) 
			{
				$exec = 'show route protocol bgp '.$exec_exp[1].' exact detail all';
			}
			else if (preg_match("/^show bgp re\s+(.*)$/", $exec, $exec_exp)) 
			{
				$re = $exec_exp[1];

				if (!preg_match('/^\^/', $re))
				{
					$re = "^.*".$re;
				}

				if (!preg_match('/\$$/', $re))
				{
					$re = $re.".*\$";
				}

				$exec = 'show route aspath-regex "'.str_replace('_', ' ', $re).'" all';
			}
		}

		if ($command == 'graph')
		{
			$ER = error_reporting(0);

			@include 'Image/GraphViz.php';

			if (!class_exists('Image_GraphViz'))
			{
				print '<div class="center"><p class="error">Class Image_GraphViz not found!</p></div>';
			}
			else
			{
				if (isset($_REQUEST['render']))
				{
					$format = ($_REQUEST['render'] == 'png') ? 'png' : 'svg';

					if ($output = process($url, $exec, TRUE) AND $as_bgp_path = parse_bgp_path($output))
					{
						$as_best_path = $as_bgp_path['best'];
						$as_pathes = $as_bgp_path['pathes'];

						if (sizeof($as_pathes) < 1)
						{
							get_blank_graph('Not found BGP information of request.', $format);
						}

						get_path_graph($router, $query, $as_pathes, $as_best_path, $format);
					}

					get_blank_graph('Unable to get BGP information.', $format);
				}
?>
		<div class="center">
			<p>BGP routing graph for <b><?php print $query ?></b>, router: <b><?php print $_CONFIG['routers'][$router]['description'] ?></b></p>
			<p><a href="?command=bgp&amp;protocol=<?php print $protocol ?>&amp;query=<?php print $query ?>&amp;router=<?php print $router ?>">Run a bgp command on this router</a></p>
			<table border="0" class="legend">
				<tr><td bgcolor="#CCCCFF" width="15">&nbsp;</td><td>Upstream AS</td><td width="80">&nbsp;</td><td bgcolor="#CCFFCC" width="15">&nbsp;</td><td>Peering AS</td><td width="80">&nbsp;</td><td bgcolor="white"><div style="height:12px;width:37px;background-image:url('data:image/gif;base64,R0lGODlhJQAMAOcAAAQCBISChJQ2NMTCxERCRcSCTGRiZYRWNEw2HKSipOTi5CQiJWRCJORCROyiXPzClISGnFRSVXRydKyi5DQyNRQSFJSSqdwCBPTy99xqlNTS1PwiJNSGvMSm7FxGRGxijIR+fPzk5JySzPSKVLS0tCQlNFxSdCQXDERCXOTC3LS21Dw6PSwCBNQeHAwKDPxSLHxyo+x2hNTS9MTG5KRyROzs7JSGwRwaHeyitPzU1KSivGxsbGRagVxaXXx8fLSq8ZycnPw0NOSaXLyCTExKTrxqbHwiJPylpDQzRPz+/GxuhCwqN4yOjJRkPEQ+WeTk/CwsLPRCTKSa3PwUFBQOCtza/Py0tNSPVGxKLIyKn1RVZBQWHPwDBPz09PxkZPybnIR3rFxcbExKZPzExDw6VPx8fNzO/KyqxJyatPQeLBQCBMzMzERGVGRldFw+JDQiFHRyhNzb3PQqPPTu/PyEhLy8vLyy/AwNFPxVVMzK6pSOxBweLOza7Lyq9HxunDQuQfxMTLwmJPzavPyUlMyb3JxaXLx+TFRKbCQeLOxufKysrPSuvORKZMSy/OTO7Hx+lPwKDIyCt/x0dPzc3Pw8PPysrPy8vPxsbPzNzPyMjPxaXIyKjMyOzHRqlcyKVCweFLy+3JSWlNyWW4R+tJxqPMTC3LSyzFw6PPwaHPwsLPzs7AwGBFQ5JGxGLPSiYLSi5hwSDKSV1ExCXLy61tzW/Dw1TJSSlOzi9PxERKya3BwWJMQqLISClNTW5CwmNKx2ROzs/PSmtGxsfHx7jJyarOyeXJxmPHRNLFxafExKbJyOzIx+fPR2hIx+tAQGBERGRWRmZOTm5CQmJWRGLPzGnFRWVDQ2NZSWrPT298Sq9GxmjlxWeTw+PQwODNTW9pSKxBweHaSmvWRehFxeXExOTCwuNNze/IyOpExOXDw+TKyuypyetXR2iMzO7Lyu+FROcPwODHRunPweHfSmZLSm7ExGZOzm/Kye44SGhMTGxIRaNKSmpHR2dBQWFNTW1PwmJCwAAAAAJQAMAAAIagCTCBxIsKBBVQYTKlxo8AsXL5YYSpzokAuXKZIwTdxYsKJFi/LK5OC40ePHj6kyhRh4pKXLlzBjXjpJ82OQRElq6tw5ZWfNKCQb+rQYZFDQhCZPpqIz6ajCpCFHOl1YccqlMVMnHoG4MSAAOw==')"></div></td><td>Best route</td></tr>
			</table>
			<br>
			<div id="loading" style="display:inline"><p><b>Please wait...</b></p></div>
			<!--[if IE]>
				<p><img src="?command=graph&amp;protocol=<?php print $protocol ?>&amp;query=<?php print $query ?>&amp;router=<?php print $router ?>&amp;render=png" alt="" title=""></p>
			<![endif]-->
			<![if ! IE]>
				<object data="?command=graph&amp;protocol=<?php print $protocol ?>&amp;query=<?php print $query ?>&amp;router=<?php print $router ?>&amp;render=true" type="image/svg+xml"></object>
			<![endif]>
			<br>
		</div>
<?php
			}

			error_reporting($ER);
		}
		else
		{
			print '<p><b>Router:</b> '.$_CONFIG['routers'][$router]['description'].'<br><b>Command:</b> '.$exec.'</p><pre><code>';
			flush();

			process($url, $exec);

			print '</code></pre>';
		}
	}
}
else
{
	$routers = group_routers($_CONFIG['routers']);

// HTML form
?>
		<form method="get" action="">
		<div class="center">
			<table class="form" cellpadding="2" cellspacing="2">
				<tr><th>Type of Query</th><th>Additional parameters</th><th>Node</th></tr>
				<tr><td>
				<table border="0" cellpadding="2" cellspacing="2">
					<tr><td><input type="radio" name="command" id="bgp" value="bgp" checked="checked"></td><td><label for="bgp">bgp</label></td></tr>
					<tr><td><input type="radio" name="command" id="advertised-routes" value="advertised-routes"></td><td><label for="advertised-routes">bgp&nbsp;advertised-routes</label></td></tr>
					<tr><td><input type="radio" name="command" id="summary" value="summary"></td><td><label for="summary">bgp&nbsp;summary</label></td></tr>
					<tr><td><input type="radio" name="command" id="graph" value="graph"></td><td><label for="graph">bgp graph</label></td></tr>
					<tr><td><input type="radio" name="command" id="trace" value="trace"></td><td><label for="trace">traceroute</label></td></tr>
					<tr><td><input type="radio" name="command" id="ping" value="ping"></td><td><label for="ping">ping</label></td></tr>
					<tr><td></td><td style="padding-top:10px">
					<select name="protocol">
						<option value="ipv4">IPv4</option>
						<option value="ipv6">IPv6</option>
					</select></td></tr>
				</table></td>
				<td align="center"><input name="query" size="30"></td>
				<td align="right">
				<select name="router" style="min-width: 180px">
<?php foreach ($routers as $group => $group_data): ?>
<?php if ($group != ''): ?>
					<optgroup label="<?php print htmlspecialchars($group) ?>">
<?php endif ?>
<?php foreach ($group_data as $router_id => $router_data): ?>
					<option value="<?php print $router_id ?>"><?php print htmlspecialchars($router_data['description']) ?></option>
<?php endforeach ?>
<?php if ($group != ''): ?>
					</optgroup>
<?php endif ?>
<?php endforeach ?>
				</select></td></tr>
				<tr><td align="center" colspan="3"><p><input type="submit" value="Submit"> | <input type="reset" value="Reset"></p></td></tr>
			</table>
		</div>
		</form>
<?php
}

// HTML footer
?>
		<hr>
		<div class="center">
			<p><small>Information: <a href="https://stat.ripe.net/AS<?php print $_CONFIG['asn'] ?>" target="_blank">RIPEstat</a> <a href="http://bgp.he.net/AS<?php print $_CONFIG['asn'] ?>" target="_blank">he.net</a> <a href="https://www.robtex.com/as/AS<?php print $_CONFIG['asn'] ?>.html" target="_blank">robtex.com</a> <a href="http://www.peeringdb.com/view.php?asn=<?php print $_CONFIG['asn'] ?>" target="_blank">PeeringDB</a></small></p>
			<p>Copyright &copy; <?php print date('Y') ?> <?php print htmlspecialchars($_CONFIG['company']) ?></p>
		</div>
	</body>
</html>
<?php

// ------------------------------------------------------------------------

/**
 * Execute command and print output
 */
function process($url, $exec, $return_buffer = FALSE)
{
	global $_CONFIG, $router, $protocol, $os, $command, $query, $ros;

	$buffer = '';
	$lines = $line = $is_exception = FALSE;
	$index = 0;
	$str_in = array();

	switch ($url['scheme'])
	{
		case 'ssh':
			switch ($_CONFIG['sshcommand'])
			{
				// Use sshpass command
				case 'sshpass':
					$ssh_path = $_CONFIG['sshpass'];
					$params = array();

					if (isset($url['pass']) AND $url['pass'] != '')
					{
						$params[] = '-p '.$url['pass'];
					}

					$params[] = 'ssh';

					if (isset($url['user']) AND $url['user'] != '')
					{
						$params[] = '-l '.$url['user'];
					}

					if (isset($url['port']) AND $url['port'] != '')
					{
						$params[] = '-p '.$url['port'];
					}

					$params[] = '-o StrictHostKeyChecking=no';
					break;

				// Use plink command
				case 'plink':
				default:
					$ssh_path = $_CONFIG['plink'];
					$params = array('-ssh');

					if (isset($url['user']) AND $url['user'] != '')
					{
						$params[] = '-l '.$url['user'];
					}

					if (isset($url['pass']) AND $url['pass'] != '')
					{
						$params[] = '-pw '.$url['pass'];
					}

					if (isset($url['port']) AND $url['port'] != '')
					{
						$params[] = '-P '.$url['port'];
					}
			}

			$params[] = $url['host'];

			$exec = escapeshellcmd($exec)."\n";

			// Get MikroTik additional summary information
			if (preg_match('/^\/routing bgp peer print status/i', $exec) AND $os == 'mikrotik' AND $return_buffer != TRUE)
			{
				if ($instance = @shell_exec('echo n | '.$ssh_path.' '.implode(' ', $params).' /routing bgp instance print'))
				{
					$instance_list = parse_list($instance);

					print 'BGP router identifier '.$instance_list['router-id'].', local AS number '.link_as($instance_list['as'])."\n";
				}
			}

			// Get MikroTik version for traceroute
			if (preg_match('/^\/tool traceroute/i', $exec) AND $os == 'mikrotik' AND $return_buffer != TRUE)
			{
				if ($instance = @shell_exec('echo n | '.$ssh_path.' '.implode(' ', $params).' /system resource print'))
				{
					if (preg_match('/version: (\d+)/', $instance, $ver))
					{
						if (isset($ver[1]))
						{
							if ($ver[1] == 6)
							{
								$exec .= ' count=1';
							}
							$ros = $ver[1];
						}
					}
				}
				$exec .= "\n";
			}

			// Huawei disable screen breaks (issue #21) -- needs more tests
			/*if ($os == 'huawei')
			{
				@shell_exec('echo n | '.$ssh_path.' '.implode(' ', $params).' screen-length 0 temporary');
			}*/

			if ($fp = @popen('echo n | '.$ssh_path.' '.implode(' ', $params).' '.$exec, 'r'))
			{
				while (!feof($fp))
				{
					if (!$output = fgets($fp, 1024))
					{
						continue;
					}

					$line = !$return_buffer ? parse_out($output, TRUE) : $output;

					if ($line === TRUE)
					{
						if (!$return_buffer)
						{
							print '<p class="error">Command aborted.</p>';
						}

						break;
					}

					if ($line === FALSE OR $return_buffer === TRUE)
					{
						$lines .= $output;
						continue;
					}

					print $line;
					flush();

					if ($line === NULL)
					{
						$line = $output;
					}
				}

				pclose($fp);
			}

			if (!$line)
			{
				print '<p class="error">Command failed.</p>';
			}

			break;

		case 'telnet':
			if (!isset($url['port']) OR $url['port'] == '')
			{
				$url['port'] = 23;
			}

			if (!isset($url['user']) OR $url['user'] == '')
			{
				$url['user'] = FALSE;
			}

			if (!isset($url['pass']) OR $url['pass'] == '')
			{
				$url['pass'] = FALSE;
			}

			try
			{
				if ($os == 'mikrotik')
				{
					$url['user'] .= '+ct';
					$prompt = '/[^\s]{2,} [>]/';
				}
				else
				{
					$prompt = '/[^\s]{2,}[\$%>] {0,1}$/';
				}

				$exec .= "\n";

				$telnet = new Telnet($url['host'], $url['port'], 10, $prompt);
				$telnet->connect();
				$telnet->login($url['user'], $url['pass']);

				// Huawei disable screen breaks (issue #21) -- needs more tests
				/*if ($os == 'huawei')
				{
					$telnet->write('screen-length 0 temporary');
				}*/

				$telnet->write(($os == 'junos') ? $exec.' | no-more' : $exec);

				$i = $j = 0;

				do
				{
					$c = $telnet->getc();

					if ($c === false)
					{
						break;
					}

					if ($c == $telnet->IAC)
					{
						if ($telnet->negotiateTelnetOptions())
						{
							continue;
						}
					}

					$buffer .= $c;

					// Clear buffer berofe backspace or escape notice
					if ($c == "\x08" OR $buffer == "Type escape sequence to abort.\r\n")
					{
						$buffer = '';

						continue;
					}

					//$c = preg_replace('/\[\d;?(\d+)?;?(\d)?m/x', ' ', $c); // Strip remaining
					//$c = preg_replace('/\x1B\x5B\x30\x6D/x', '\x0A', $c); // Convert to \n
					$buffer = preg_replace('/[\x80-\xFF]/x', ' ', $buffer); // Strip Ext ASCII
					$buffer = preg_replace('/[\x00-\x09]/x', ' ', $buffer); // Strip Low ASCII // \x0B-\x1F
					$buffer = preg_replace('/\x1b\x5b;?([^\x6d]+)?\x6d/x', '', $buffer); // Strip colors

					if (preg_match($prompt, substr($buffer, -4)))
					{
						if ($j < 2)
						{
							$j++;

							if (substr($buffer, -2) != "\r\n")
							{
								$telnet->write();
								$i = 0;

								continue;
							}
						}

						$telnet->disconnect();
						break;
					}

					if (substr($buffer, -10) == ' --More-- ')
					{
						$telnet->write();
					}
					else if (substr($buffer, -2) == "\r\n")
					{
						// Clear buffer on first empty line
						if ($i == 1 AND $buffer == "\r\n")
						{
							$buffer = '';
						}

						// JunOS
						if (strpos($buffer, '---(more)---') !== FALSE)
						{
							$buffer = ltrim(str_replace('---(more)---', '', $buffer), "\r\n");
						}

						$i++;

						if ($i > 1)
						{
							$line = !$return_buffer ? parse_out($buffer, TRUE) : $buffer;

							if ($line === TRUE)
							{
								if (!$return_buffer)
								{
									print '<p class="error">Command aborted.</p>';
								}

								$telnet->disconnect();
								break;
							}

							if ($line === FALSE OR $return_buffer === TRUE)
							{
								$lines .= $buffer;
							}
							else
							{
								print $line;
								flush();

								if ($line === NULL)
								{
									$line = $buffer;
								}
							}
						}

						$buffer = '';
					}
				}
				while ($c != $telnet->NULL OR $c != $telnet->DC1);

				if (!$line)
				{
					print '<p class="error">Command failed.</p>';
				}
			}
			catch (Exception $exception) 
			{
				$is_exception = TRUE;

				if (!$return_buffer)
				{
					print '<p class="error">Telnet error: '.$exception->getMessage().'</p>';
				}
			}

			break;
	}

	if ($lines)
	{
		if ($return_buffer)
		{
			return $lines;
		}

		if ($line = parse_out($lines))
		{
			print $line;
		}
	}

	flush();
}

/**
 * Parse output contents
 */
function parse_out($output, $check = FALSE)
{
	global $_CONFIG, $router, $protocol, $os, $command, $exec, $query, $index, $lastip, $best, $count, $str_in, $ros;

	$output = str_replace("\r\n", "\n", $output);

	// MikroTik
	if (preg_match("/^\/(ip|ipv6) route print detail/i", $exec) AND $os == 'mikrotik')
	{
		if ($check)
		{
			return FALSE;
		}

		$output_parts = explode("\n" , trim($output), ($protocol != 'ipv6' ? 4 : 3));

		if (!isset($output_parts[($protocol != 'ipv6' ? 3 : 2)]))
		{
			return 'Records for '.strip_tags($query).' is not found';
		}

		$summary_parts = explode("\n\n" , $output_parts[($protocol != 'ipv6' ? 3 : 2)]);

		$output = implode("\n", array_slice($output_parts, 0, ($protocol != 'ipv6' ? 3 : 2)))."\n\n";

		foreach ($summary_parts as $summary_part)
		{
			$data_exp = explode(' ', trim($summary_part), 3);

			$summary_part = preg_replace_callback(
				"/bgp-as-path=\"([^\"]+)\"/x",
				function ($matches) {
					return stripslashes('bgp-as-path=\"'.link_as($matches[1]).'\"');
				},
				$summary_part
			);

			if (strpos($data_exp[1], 'A') !== FALSE)
			{
				$output .= '<span style="color:#ff0000">'.$summary_part."</span>\n\n";
			}
			else
			{
				$output .= $summary_part."\n\n";
			}
		}

		return $output;
	}

	// MikroTik
	if (preg_match("/^\/routing bgp advertisements print/i", $exec) AND $os == 'mikrotik')
	{
		return preg_replace_callback(
			"/^(.{8}\s)([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", $matches[2]).$matches[3];
			},
			$output
		);
	}

	// MikroTik
	if (preg_match("/^\/(ip|ipv6) route print/i", $exec) AND $os == 'mikrotik')
	{
		return preg_replace_callback(
			"/(^[\s\d]+)(\s+[A-z]+\s+)([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) {
				return $matches[1].$matches[2].link_command("bgp", $matches[3]).$matches[4];
			},
			$output
		);
	}

	// MikroTik
	if (preg_match("/^\/routing bgp peer print status/i", $exec) AND $os == 'mikrotik')
	{
		if ($check)
		{
			return FALSE;
		}

		$output_parts = explode("\n" , trim($output), 2);

		if (!isset($output_parts[1]))
		{
			return 'Records for '.strip_tags($query).' is not found';
		}

		$summary_parts = explode("\n\n" , $output_parts[1]);

		$table_array[] = array
		(
			'remote-address' => 'Neighbor',
			'name' => 'PeerName',
			'remote-id' => 'RemoteID',
			'remote-as' => 'AS',
			'withdrawn-received' => 'MsgRcvd',
			'updates-received' => 'MsgSent',
			'uptime' => 'Up/Down',
			'state' => 'State',
			'prefix-count' => 'PfxRcd',
			'updates-sent' => 'PfxSnd',
		);

		foreach ($summary_parts as $summary_part)
		{
			$data_exp = explode(' ', trim($summary_part), 3);

			if (sizeof($data_exp) != 3)
			{
				continue;
			}

			$data = parse_list($data_exp[2]);

			$table_array[] = array
			(
				'remote-address' => isset($data['remote-address']) ? link_whois($data['remote-address']) : '',
				'name' => isset($data['name']) ? $data['name'] : '',
				'remote-id' => isset($data['remote-id']) ? $data['remote-id'] : '',
				'remote-as' => isset($data['remote-as']) ? link_as($data['remote-as']) : '',
				'withdrawn-received' => isset($data['withdrawn-received']) ? $data['withdrawn-received'] : '',
				'updates-received' => isset($data['updates-received']) ? $data['updates-received'] : '',
				'uptime' => isset($data['uptime']) ? $data['uptime'] : '',
				'state' => $data_exp[1],
				'prefix-count' => (isset($data['remote-address']) AND isset($data['prefix-count'])) 
									? link_command('routes', $data['remote-address'], $data['prefix-count'])
									: '',
				'updates-sent' => (isset($data['name']) AND isset($data['updates-sent']))
									? link_command('advertised-routes', $data['name'], $data['updates-sent'])
									: '',
			);
		}

		return 
			str_replace('Flags', 'State', $output_parts[0])."\n\n".
			build_table($table_array)."\n\n".
			'Total number of neighbors '.(sizeof($table_array) - 1);
	}

	// MikroTik
	if (preg_match("/^\/ping/i", $exec) AND $os == 'mikrotik')
	{
		if (preg_match('/^HOST/', $output) AND $index == 0)
		{
			$index++;

			return 'PING '.$query.' ('.get_ptr($query).'): 56 data bytes'."\n";
		}
	
		if ($index > 0)
		{
			$exp = explode(' ', preg_replace('/[\s\t]+/', ' ', trim($output)));
			$exp = array_slice($exp, 0, 4);

			if (!$exp OR $exp[0] == '' OR $exp[0] == 'HOST' OR (isset($exp[1]) AND $exp[1] == 'timeout'))
			{
				return;
			}

			if (preg_match('/sent=([0-9]+) received=([0-9]+) packet-loss=([^ ]+)/', $output, $stat))
			{
				$return = "\n".'--- '.$query.' ping statistics ---'."\n".
						$stat[1].' packets transmitted, '.$stat[2].' packets received, '.trim($stat[3]).' packet loss'."\n";

				if (preg_match('/min-rtt=([0-9]+)ms avg-rtt=([0-9]+)ms/', $output, $stat2))
				{
					$return .= 'round-trip min/avg/max = '.$stat2[1].'/'.$stat2[2];
				}

				if (preg_match('/max-rtt=([0-9]+)ms/', $output, $stat3))
				{
					$return .= '/'.$stat3[1];
				}

				return $return;
			}

			// Пинг больше 99, отдача max-rtt отдельной строкой
			if (preg_match('/max-rtt=([0-9]+)ms/', $output, $stat3))
			{
				$return = '/'.$stat3[1];

				return $return;
			}

			$index++;

			return $exp[1].' bytes from '.$exp[0].': icmp_seq='.intval($index-1).' ttl='.$exp[2].' time='.$exp[3]."\n";
		}
	}

	// MikroTik
	if (preg_match("/^\/tool trace/i", $exec) AND $os == 'mikrotik' AND $ros >= 6)
	{
		if (preg_match('/^ # ADDRESS/', $output) AND $index == 0)
		{
			$index++;

			return 'traceroute to '.$query.' ('.get_ptr($query).'), 64 hops max, 60 byte packets'."\n";
		}

		if ($index > 0)
		{
			$exp = explode(' ', preg_replace('/[\s\t]+/', ' ', trim($output)));
			$exp = array_slice($exp, 0, 9);

			if (!$exp OR empty($exp[0]) OR (sizeof($exp) == 4 AND isset($exp[3]) AND $exp[3] != 'timeout'))
			{
				return;
			}

			$new_exp[0] = (strlen($exp[0]) < 2) ? ' '.$exp[0] : $exp[0];

			if ($exp[3] == 'timeout')
			{
				$new_exp[1] = '* * *';
				$new_exp = array_slice($new_exp, 0, 2);
			}
			else
			{
				$radb = get_radb($exp[1]);

				$new_exp[1] = get_ptr($exp[1]);
				$new_exp[2] = '('.$exp[1].')';
				$new_exp[3] = '['.(isset($radb['origin']) ? 'AS '.link_as($radb['origin']) : '').']';
				$new_exp[4] = $exp[5].'ms';
				$new_exp[5] = $exp[6].'ms';
				$new_exp[6] = $exp[7].'ms';
			}

			if ($index >= 64)
			{
				return TRUE;
			}

			$str = implode(' ', $new_exp)."\r\n";

			if (@in_array($str, $str_in))
			{
				return;
			}

			$str_in[] = $str;

			$index++;

			return $str;
		}
	}

	// MikroTik
	if (preg_match("/^\/tool trace/i", $exec) AND $os == 'mikrotik' AND $ros <= 5)
	{
		if (preg_match('/^ # ADDRESS/', $output) AND $index == 0)
		{
			$index++;

			return 'traceroute to '.$query.' ('.get_ptr($query).'), 64 hops max, 60 byte packets'."\n";
		}

		if ($index > 0)
		{
			$exp = explode(' ', preg_replace('/[\s\t]+/', ' ', trim($output)));
			$exp = array_slice($exp, 0, 5);

			if (!$exp OR empty($exp[0]))
			{
				return;
			}

			$exp[0] = (strlen($exp[0]) < 2) ? ' '.$exp[0] : $exp[0];

			if ($exp[1] == '0.0.0.0' OR $exp[1] = '::')
			{
				$exp[1] = '* * *';
				$exp = array_slice($exp, 0, 2);
			}
			else
			{
				$radb = get_radb($exp[1]);

				$exp[6] = $exp[4];
				$exp[5] = $exp[3];
				$exp[4] = $exp[2];
				$exp[3] = '['.(isset($radb['origin']) ? 'AS '.link_as($radb['origin']) : '').']';
				$exp[2] = '('.$exp[1].')';
				$exp[1] = get_ptr($exp[1]);
			}

			if ($index >= 64)
			{
				return TRUE;
			}

			$index++;

			return implode(' ', $exp)."\r\n";
		}
	}

	// Other OS parsers
	if (preg_match('/^show bgp ipv6 unicast/i', $exec)  AND $os = 'ios') 
	{
		$output = str_replace('% Incomplete command.', '<p class="error">Please use network prefix in Parameter.</p>', $output);
	}

	if ($exec == 'show ip bgp summary')
	{
		$output = preg_replace_callback(
			"/( local AS number )(\d+)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]);
			},
			$output
		);

		// JunOS
		if ($os == 'junos')
		{
			$output = preg_replace_callback(
				"/^([\dA-Fa-f:\.]+\s+)(\d+)/",
				function ($matches) {
					return $matches[1].link_as($matches[2]);
				},
				$output
			);
		}
		else
		{
			$output = preg_replace_callback(
				"/^([\d\.]+\s+\d+\s+)(\d+)/",
				function ($matches) {
					return $matches[1].link_as($matches[2]);
				},
				$output
			);
		}

		$output = preg_replace_callback(
			"/^(\d+\.\d+\.\d+\.\d+)(\s+.*\s+)([1-9]\d*)\n$/",
			function ($matches) {
				return $matches[1].$matches[2].link_command("routes", $matches[1], $matches[3])."\n";
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(\d+\.\d+\.\d+\.\d+)(\s+)/",
			function ($matches) {
				return link_command("bgp", "neighbors+".$matches[1], $matches[1]).$matches[2];
			},
			$output
		);

		// IPv6 neighbours
		$output = preg_replace_callback(
			"/^(.{15} 4\s+)(\d+)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\dA-Fa-f]*:[\dA-Fa-f:]*)(\s+)/",
			function ($matches) {
				return link_command("bgp", "neighbors+".$matches[1], $matches[1]).$matches[2];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\dA-Fa-f]*:[\dA-Fa-f:]*)\n$/",
			function ($matches) {
				return link_command("bgp", "neighbors+".$matches[1], $matches[1])."\n";
			},
			$output
		);

		return $output;
	}

	if ($exec == 'show ipv6 bgp summary' OR $exec == 'show bgp ipv6 unicast summary') 
	{
		$output = preg_replace_callback(
			"/^(.{15} 4\s+)(\d+)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]);
			},
			$output
		);

		if (preg_match("/^([\dA-Fa-f]*:[\dA-Fa-f:]*)\s+4\s+/", $output, $lastip_exp))
		{
			$lastip = $lastip_exp[1];

			$output = preg_replace_callback(
				"/^(.{15} 4\s+)(\d+)/",
				function ($matches) {
					return $matches[1].link_as($matches[2]);
				},
				$output
			);
			$output = preg_replace_callback(
				"/^([\dA-Fa-f:]+)(\s+.*\s+)([1-9]\d*)$/",
				function ($matches) use ($lastip) {
					return link_command("routes", $lastip, $matches[3]);
				},
				$output
			);
			$output = preg_replace_callback(
				"/^([\dA-Fa-f:]+)(\s+)/",
				function ($matches) {
					return link_command("routes", $matches[1]).$matches[2];
				},
				$output
			);

			unset($lastip);
		}

		if (preg_match("/^([\dA-Fa-f:]+)\n$/", $output, $lastip_exp))
		{
			$lastip = $lastip_exp[1];

			$output = preg_replace_callback(
				"/^([\dA-Fa-f:]+)/",
				function ($matches) {
					return link_command("routes", $matches[1]);
				},
				$output
			);
		}

		if (isset($lastip) AND preg_match("/^(\s+.*\s+)([1-9]\d*)\n$/", $output)) 
		{
			$output = preg_replace_callback(
				"/^(\s+.*\s+)([1-9]\d*)\n$/",
				function ($matches) use ($lastip) {
					return $matches[1].link_command("routes", $lastip, $matches[2])."\n";
				},
				$output
			);

			unset($lastip);
		}

		return $output;
	}

	if ($exec == 'show bgp summary') 
	{
		// JunOS
		if (preg_match("/^([\dA-Fa-f:][\d\.A-Fa-f:]+)\s+/", $output, $lastip_exp)) 
		{
			$lastip = $lastip_exp[1];

			// IPv4
			$output = preg_replace_callback(
				"/^(\d+\.\d+\.\d+\.\d+)(\s+.*\s+)([1-9]\d*)(\s+\d+\s+\d+\s+\d+\s+\d+\s+[\d:]+\s+)(\d+)\/(\d+)\/(\d+)(\s+)/",
				function ($matches) {
					return $matches[1].$matches[2].
						link_command("routes", $matches[1], $matches[3]).$matches[4].
						link_command("routes", $matches[1], $matches[5])."/".
						link_command("routes", $matches[1], $matches[6])."/".
						link_command("routes", $matches[1], $matches[7]).$matches[8];
				},
				$output
			);

			// IPv4/IPv6
			$output = preg_replace_callback(
				"/^([\dA-Fa-f:][\d\.A-Fa-f:]+\s+)(\d+)(\s+)/",
				function ($matches) {
					return $matches[1].link_as($matches[2]).$matches[3];
				},
				$output
			);
			$output = preg_replace_callback(
				"/^([\dA-Fa-f:][\d\.A-Fa-f:]+)(\s+)/",
				function ($matches) {
					return link_command("bgp", "neighbors+".$matches[1], $matches[1]).$matches[2];
				},
				$output
			);
		}

		if (isset($lastip) AND preg_match("/(  [^:]+: )(\d+)\/(\d+)\/(\d+)$/", $output))
		{
			$output = preg_replace_callback(
				"/^(  [^:]+: )(\d+)\/(\d+)\/(\d+)\n$/",
				function ($matches) use ($lastip) {
					return "\\1".
						link_command("routes", $lastip, $matches[2])."/".
						link_command("bgp", "neighbors+".$lastip."+routes+all", $matches[3])."/".
						link_command("bgp", "neighbors+".$lastip."+routes+damping+suppressed", $matches[4])."\n";
				},
				$output
			);

			unset($lastip);
		}

		return $output;
	}

	if (preg_match("/^show ip bgp\s+n\w*\s+[\d\.]+\s+(ro|re|a)/i", $exec) OR
		preg_match("/^show (ipv6 bgp|bgp ipv6)\s+n\w*\s+[\dA-Fa-f:]+\s+(ro|re|a)/i", $exec) OR
		preg_match("/^show (ipv6 bgp|bgp ipv6)\s+re/i", $exec) OR
		preg_match("/^show ip bgp\s+[\d\.]+\s+[\d\.]+\s+(l|s)/i", $exec) OR
		preg_match("/^show (ip bgp|bgp ipv6) prefix-list/i", $exec) OR
		preg_match("/^show (ip bgp|bgp ipv6) route-map/i", $exec))
	{
		$output = preg_replace_callback(
			"/^([\*r ](>|d|h| ).{59})([\d\s,\{\}]+)([ie\?])\n$/",
			function ($matches) {
				return $matches[1].link_as($matches[3]).$matches[4]."\n";
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\*r ](>|d|h| )[i ])([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", $matches[3]).$matches[4];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\*r ](>|d|h| )[i ])([\d\.A-Fa-f:\/]+)\n$/",
			function ($matches) {
				return $matches[1].link_command("bgp", $matches[3])."\n";
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(( ){20}.{41})([\d\s,\{\}]+)([ie\?])\n$/",
			function ($matches) {
				return $matches[1].link_as($matches[3]).$matches[4]."\n";
			},
			$output
		);
		$output = preg_replace_callback(
			"/(, remote AS )(\d+)(,)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]).$matches[3]."\n";
			},
			$output
		);

		return $output;
	}

	// JunOS
	if (preg_match("/^show route receive-protocol bgp\s+[\d\.A-Fa-f:]+/i", $exec)) 
	{
		$output = preg_replace_callback(
			"/(Community: )([\d: ]+)/",
			function ($matches) {
				return $matches[1].link_community($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(Communities: )([\d: ]+)/",
			function ($matches) {
				return $matches[1].link_community($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(^\s+AS path: )([\d ]+)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.\s].{24})([\d\.]+)(\s+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "neighbors+".$matches[2], $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.\/]+)(\s+)/",
			function ($matches) {
				return link_command("bgp", $matches[1]).$matches[2];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) {
				return link_command("bgp", $matches[1]."+exact", $matches[1]).$matches[2];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.A-Fa-f:\/]+)\s*\n$/",
			function ($matches) {
				return link_command("bgp", $matches[1]."+exact", $matches[1]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([ \*] )([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", $matches[2]."+exact", $matches[2]).$matches[3];
			},
			$output
		);

		return $output;
	}

	// JunOS
	if (preg_match("/^show route advertising-protocol bgp\s+([\d\.A-Fa-f:]+)$/i", $exec, $ip_exp))
	{
		$ip = $ip_exp[1];

		$output = preg_replace_callback(
			"/^([\d\.\s].{64})([\d\s,\{\}]+)([I\?])\n$/",
			function ($matches) {
				return $matches[1].link_as($matches[2]).$matches[3]."\n";
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.\s].{24})([\d\.]+)(\s+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "neighbors+".$matches[2], $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.\/]+)(\s+)/",
			function ($matches) {
				return link_command("bgp", $matches[1]).$matches[2];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) {
				return link_command("bgp", $matches[1]."+exact", $matches[1]).$matches[2];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([\d\.A-Fa-f:\/]+)\s*\n$/",
			function ($matches) {
				return link_command("bgp", $matches[1]."+exact", $matches[1])."\n";
			},
			$output
		);
		$output = preg_replace_callback(
			"/^([ \*] )([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("bgp", "neighbors+".$ip."+advertised-routes+".$matches[2], $matches[2]).$matches[3];
			},
			$output
		);

		return $output;
	}

	if (preg_match("/^show ip bgp n\w*\s+([\d\.]+)/i", $exec) OR
			 preg_match("/^show ip bgp n\w*$/i", $exec))
	{
		if (!isset($lastip) AND preg_match("/^BGP neighbor is ([\d\.]+)(,?)/", $output, $lastip_exp))
		{
			$lastip = $lastip_exp[1];
		}

		$output = preg_replace_callback(
			"/(Prefix )(advertised)( [1-9]\d*)/",
			function ($matches) use ($lastip) {
				return $matches[1].link_command("advertised-routes", $lastip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/(    Prefixes Total:                 )(\d+)( )/",
			function ($matches) use ($lastip) {
				return $matches[1].link_command("advertised-routes", $lastip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/(prefixes )(received)( [1-9]\d*)/",
			function ($matches) use ($lastip) {
				return $matches[1].link_command("routes", $lastip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/(    Prefixes Current: \s+)(\d+)(\s+)(\d+)/",
			function ($matches) use ($lastip) {
				return $matches[1].
					link_command("advertised-routes", $lastip, $matches[2]).$matches[3].
					link_command("routes", $lastip, $matches[4]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(\s+)(Received)( prefixes:\s+[1-9]\d*)/",
			function ($matches) use ($lastip) {
				return $matches[1].link_command("routes", $lastip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/(    Saved \(soft-reconfig\):\s+)(\d+|n\/a)(\s+)(\d+)/",
			function ($matches) use ($lastip) {
				return $matches[1].$matches[2].$matches[3].link_command("received-routes", $lastip, $matches[4]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/( [1-9]\d* )(accepted)( prefixes)/",
			function ($matches) use ($lastip) {
				return $matches[1].link_command("routes", $lastip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(  [1-9]\d* )(accepted|denied but saved)( prefixes consume \d+ bytes)/",
			function ($matches) use ($lastip) {
				return $matches[1].link_command("received-routes", $lastip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(BGP neighbor is )(\d+\.\d+\.\d+\.\d+)(,?)/",
			function ($matches) {
				return $matches[1].link_whois($matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace("/^( Description: )(.*)$/", '\\1<b>\\2</b>', $output);
		$output = preg_replace_callback(
			"/(,\s+remote AS )(\d+)(,)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/(, local AS )(\d+)(,)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/( update prefix filter list is\s+:?\*?)(\S+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "prefix-list+".$matches[2], $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(Route map for \S+ advertisements is\s+:?\*?)(\S+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "route-map+".$matches[2], $matches[2]);
			},
			$output
		);

		return $output;
	}

	if (preg_match("/^show (ipv6 bgp|bgp ipv6) n\w*\s+([\dA-Fa-f:]+)/i", $exec, $ip_exp))
	{
		$ip = $ip_exp[1];

		$output = preg_replace_callback(
			"/(Prefix )(advertised)( [1-9]\d*)/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("advertised-routes", "'.$ip.'", $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/( [1-9]\d* )(accepted)( prefixes)/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("routes", $ip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace("/^( Description: )(.*)$/", '\\1<b>\\2</b>', $output);
		$output = preg_replace_callback(
			"/(\s+remote AS )(\d+)(,)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/(\s+local AS )(\d+)(,)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/( update prefix filter list is\s+:?\*?)(\S+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "prefix-list+".$matches[2], $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(Route map for \S+ advertisements is\s+:?\*?)(\S+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "route-map+".$matches[2], $matches[2]);
			},
			$output
		);

		return $output;
	}

	if (preg_match("/^show bgp n\w*\s+([\d\.A-Fa-f:]+)/i", $exec, $ip_exp))
	{
		$ip = $ip_exp[1];

		$output = preg_replace_callback(
			"/(\s+AS )(\d+)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(\s+AS: )(\d+)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(    Active prefixes:\s+)(\d+)/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("routes", $ip, $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(    Received prefixes:\s+)(\d+)/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("bgp", "neighbors+".$ip."+routes+all", $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(    Advertised prefixes:\s+)(\d+)/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("advertised-routes", $ip, $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(    Suppressed due to damping:\s+)(\d+)/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("bgp", "neighbors+".$ip."+routes+damping+suppressed", $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(  )(Export)(: )/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("advertised-routes", $ip, $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/( )(Import)(: )/",
			function ($matches) use ($ip) {
				return $matches[1].link_command("bgp", "neighbors+".$ip."+routes+all", $matches[2]).$matches[3];
			},
			$output
		);

		return $output;
	}

	// JunOS
	if (preg_match("/^show route protocol bgp .* terse/i", $exec)) 
	{
		if (preg_match("/^([\+\-\*]){1}/", $output, $exp) AND strpos($output, 'Active Route') === FALSE)
		{
			if ($exp[1] == '*' OR $exp[1] == '+') 
			{
				$best = "#ff0000";
			}
			else if ($exp[1] == '-') 
			{
				$best = "#008800";
			}
			else 
			{
				$best = '';
			}
		}

		if (isset($best) AND $best != '')
		{
			$output = '<span style="color:'.$best.'">'.$output.'</span>';
		}

		$output = preg_replace_callback(
			"/^([\* ] )([\d\.A-Fa-f:\/]+)(\s+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", $matches[2]."+exact", $matches[2]).$matches[3];
			},
			$output
		);

		return $output;
	}

	// JunOS
	if (preg_match("/^show route protocol bgp /i", $exec) OR preg_match("/^show route aspath-regex /i", $exec))
	{
		if (preg_match("/^        (.)BGP    /", $output, $exp))
		{
			if ($exp[1] == '*') 
			{
				$best = '#FF0000';
			} 
			else 
			{
				$best = '';
			}
		}
		else if (preg_match("/^:?\n?[\n\d\.A-Fa-f:\/\s]{19}([\*\+\- ]{1})\[BGP\//", $output, $exp))
		{
			if ($exp[1] == '*' OR $exp[1] == '+') 
			{
				$best = "#ff0000";
			}
			else if ($exp[1] == '-') 
			{
				$best = "#008800";
			}
			else 
			{
				$best = '';
			}
		}
		else if (preg_match("/^[\n]+$/", $output)) 
		{
			$best = '';
		}

		$output = preg_replace_callback(
			"/( from )([0-9\.A-Fa-f:]+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "neighbors+".$matches[2], $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(                Source: )([0-9\.A-Fa-f:]+)/",
			function ($matches) {
				return $matches[1].link_command("bgp", "neighbors+".$matches[2], $matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(\s+AS: )([\d ]+)/",
			function ($matches) {
				return link_as($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(Community: )([\d: ]+)/",
			function ($matches) {
				return $matches[1].link_community($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(Communities: )([\d: ]+)/",
			function ($matches) {
				return $matches[1].link_community($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(^\s+AS path: )([\d ]+)/",
			function ($matches) {
				return $matches[1].link_as($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/^(:?\n?[\dA-Fa-f:]+[\d\.A-Fa-f:\/]+)(\s*)/",
			function ($matches) {
				return "<b>".link_command("bgp", $matches[1]."+exact", $matches[1])."</b>".$matches[2];
			},
			$output
		);

		if (isset($best) AND $best != '')
		{
			$output = '<span style="color:'.$best.'">'.$output.'</span>';
		}

		return $output;
	}

	if (preg_match("/bgp/", $exec))
	{
		$output = preg_replace("|^(BGP routing table entry for) (\S+)|", "\\1 <b>\\2</b>", $output);

		if (preg_match("|^(Paths: .*) best #(\d+)|", $output, $best_exp))
		{
			$best = $best_exp[2];

			$output = preg_replace("|^(Paths: .*) best #(\d+)|", '\\1 <span style="color:#ff0000">best #\\2</span>', $output);
		}

		// Fix for IPv6 route output where there are no addional 3 spaces before addresses
		if (preg_match("/ Advertised to non peer-group peers:/", $output) AND preg_match("/ ipv6 /", $exec)) 
		{
			$count--;
		}

		if ((	 preg_match("/^  (\d+.*)/", $output) AND 
				!preg_match("/^  \d+\./", $output) AND 
				!preg_match("/[a-z\:\.]+/", $output)
			) OR 
			preg_match("/^  Local/", $output) OR
			preg_match("/, \([^)]+\)/", $output)) 
		{
			$count++;

			$output = preg_replace_callback(
				"/^([^\(A-z\n]+)/",
				function ($matches) {
					return link_as($matches[1]);
				},
				$output
			);
			$output = preg_replace_callback(
				"/(, \(aggregated by )(\d+) ([^\)]+)/",
				function ($matches) {
					return $matches[1].link_as($matches[2])." ".link_whois($matches[3]);
				},
				$output
			);
		}

		if (isset($best) AND $best == $count)
		{
			$output = '<span style="color:#ff0000">'.$output.'</span>';
		}

		$output = preg_replace_callback(
			"/( from )([0-9\.A-Fa-f:]+)( )/",
			function ($matches) {
				return $matches[1].link_command("bgp", "neighbors+".$matches[2], $matches[2]).$matches[3];
			},
			$output
		);
		$output = preg_replace_callback(
			"/(Community: )([\d: ]+)/",
			function ($matches) {
				return $matches[1].link_community($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(Communities: )([\d: ]+)/",
			function ($matches) {
				return $matches[1].link_community($matches[2]);
			},
			$output
		);
		$output = preg_replace_callback(
			"/(^\s+AS path: )([\d ]+)/",
			function ($matches) {
				return link_as($matches[2]);
			},
			$output
		);

		return $output;
	}

	if (preg_match("/^trace/", $exec))
	{
		$output = preg_replace("/\[AS0\]\s(.*)/", "\\1", $output);

		// IPv4
		$output = preg_replace_callback(
			"/(\[AS)(\d+)(\])\s(.*)(\))(.*)/",
			function ($matches) {
				return $matches[4].$matches[5]." ".$matches[1]." ".link_as($matches[2]).$matches[3].$matches[6];
			},
			$output
		);
		// IPv6
		$output = preg_replace_callback(
			"/(\[AS)(\d+)(\])\s([^\s]+)\s(.*)/",
			function ($matches) {
				return $matches[4]." ".$matches[1]." ".link_as($matches[2]).$matches[3].$matches[5];
			},
			$output
		);
		$output = preg_replace_callback(
			"/\((.*)\) (\[AS\s+)(\d+)(\])/",
			function ($matches) {
				return get_as($matches[1], $matches[3]);
			},
			$output
		);

		return $output;
	}

	return $output;
}

/**
 * Parse `bgp' output contents and return AS pathes
 */
function parse_bgp_path($output)
{
	global $os, $exec, $query, $count;

	$best = FALSE;
	$pathes = array();

	$output = str_replace("\r\n", "\n", $output);

	// MikroTik
	if (preg_match("/^\/(ip|ipv6) route print detail/i", $exec) AND $os == 'mikrotik')
	{
		$output_parts = explode("\n" , trim($output), 4);

		if (!isset($output_parts[3]))
		{
			return FALSE;
		}

		$summary_parts = explode("\n\n" , $output_parts[3]);

		foreach ($summary_parts as $i => $summary_part)
		{
			$data_exp = explode(' ', trim($summary_part), 3);

			if (strpos($data_exp[1], 'A') !== FALSE)
			{
				$best = $i;
			}

			if (preg_match("/bgp-as-path=\"([^\"]+)\"/", $summary_part, $exp))
			{
				if ($path = parse_as_path($exp[1]))
				{
					$pathes[] = $path;
				}
			}
		}

		return array
		(
			'best' => $best,
			'pathes' => $pathes
		);
	}

	// JunOS
	if (preg_match("/^show route protocol bgp .* terse/i", $exec)) 
	{
		$lines = explode("\n", $output);

		foreach ($lines as $line)
		{
			if (preg_match("/^[\+\-\*]{1}/", $line, $exp) AND strpos($line, 'Active Route') === FALSE)
			{
				$line =  preg_replace('/ {2,}/',' ',$line);
				$line = explode(' ', $line);

				if ($line[0] == '*' OR $line[0] == '+') 
				{
					if ($count == 0)
					{
						$count++;
					}
				}

				$line = array_slice($line, 6, -1);
				$line = implode(' ', $line);

				if ($path = parse_as_path($line))
				{
					$pathes[] = $path;
				}
			}
		}

		$best = $count - 1;

		return array
		(
			'best' => $best,
			'pathes' => $pathes
		);
	}

	// JunOS
	if (preg_match("/^show route protocol bgp /i", $exec) OR preg_match("/^show route aspath-regex /i", $exec))
	{
		$lines = explode("\n", $output);

		foreach ($lines as $line)
		{
			if (preg_match("/^        (.)BGP    /", $line, $exp))
			{
				if ($exp[1] == '*') 
				{
					if ($count == 0)
					{
						$count++;
					}
				}
			}
			else if (preg_match("/^:?\n?[\n\d\.A-Fa-f:\/\s]{19}([\*\+\- ]{1})\[BGP\//", $line, $exp))
			{
				if ($exp[1] == '*' OR $exp[1] == '+') 
				{
					if ($count == 0)
					{
						$count++;
					}
				}
			}

			if (preg_match("/^\s+AS path: ([\d ]+)/", $line, $exp))
			{
				if ($path = parse_as_path($exp[1]))
				{
					$pathes[] = $path;
				}
			}
		}

		$best = $count - 1;

		return array
		(
			'best' => $best,
			'pathes' => $pathes
		);
	}

	// Other OS
	if (preg_match("/bgp/", $exec))
	{
		if (preg_match("|(Paths: .*) best #(\d+)|", $output, $best_exp))
		{
			$best = $best_exp[2] - 1;
		}

		$lines = explode("\n", $output);

		foreach ($lines as $line)
		{
			if ((	 preg_match("/^  (\d+.*)/", $line) AND 
					!preg_match("/^  \d+\./", $line) AND 
					!preg_match("/[a-z\:\.]+/", $line)
				) OR 
				preg_match("/^  Local/", $line) OR
				preg_match("/, \([^)]+\)/", $line)) 
			{
				$line = preg_replace("/^([^\(A-z\n]+).*/", "\\1", $line);

				if ($path = parse_as_path($line))
				{
					$pathes[] = $path;
				}
			}
		}

		return array
		(
			'best' => $best,
			'pathes' => $pathes
		);
	}

	return FALSE;
}

/**
 * Parse list separated by spaces
 */
function parse_list($array)
{
	preg_match_all('/([^=]+)=([^\s]+)[\n\s]/is', $array, $out);

	$array = array();

	if (isset($out[1]))
	{
		foreach ($out[1] as $key => $val)
		{
			$array[trim($out[1][$key])] = trim($out[2][$key], '"');
		}
	}

	return $array;
}

/**
 * Parse AS path
 */
function parse_as_path($line)
{
	$path = preg_split('/([^\d]+)/', trim($line));

	$return = array();

	foreach ($path as $asn)
	{
		if ($asn != '')
		{
			$return[] = 'AS'.$asn;
		}
	}

	return array_unique($return);
}

/**
 * Build list table
 */
function build_table($table_array)
{
	$size_array = array();

	foreach ($table_array as $priority => $data)
	{
		foreach ($data as $field => $value)
		{
			$size_array[$field][$priority] = $value;
		}
	}

	$size_max = array();

	foreach ($size_array as $field => $value)
	{
		$size_max[$field] = array_map('mb_strlen', array_map('strip_tags', $value));
	}

	$max = array_map('max', $size_max);

	$return = '';

	foreach ($table_array as $index => $data)
	{
		$line = array();

		foreach ($data as $field => $value)
		{
			if (reset(array_keys($data)) == $field)
			{
				$line[] = $value.str_repeat(' ', $max[$field] - mb_strlen(strip_tags($value)));
			}
			else
			{
				$line[] = str_repeat(' ', $max[$field] - mb_strlen(strip_tags($value))).$value;
			}
		}

		$return .= implode('  ', $line)."\n";
	}

	return $return;
}

/**
 * Link to execute command
 */
function link_command($command, $query, $name = '', $return_uri = FALSE)
{
	global $router, $protocol;

	if ($name == '')
	{
		$name = $query;
	}

	$uri = '?router='.$router.
		'&amp;protocol='.$protocol.
		'&amp;command='.$command.
		'&amp;query='.$query;

	if ($return_uri)
	{
		return $uri;
	}

	return '<a href="'.$uri.'">'.$name.'</a>';
}

/**
 * Link to AS community
 */
function link_community($line)
{
	global $_CONFIG;

	$communitylist = preg_split('/[^\d:]+/', $line);

	foreach ($communitylist as $i => $community)
	{
		$communitylist[$i] = '<i>'.$community.'</i>';
	}

	return implode(' ', $communitylist);
}

/**
 * Link to AS whois
 */
function link_as($line, $word = FALSE)
{
	global $_CONFIG;

	//print_r($line);
	
	return preg_replace("/(?:AS)?([\d]+)/is", 
		"<a href=\"".htmlspecialchars($_CONFIG['aswhois'])."AS\\1\" target=\"_blank\">".($word ? 'AS' : '')."\\1</a>", $line);
}

function get_as($ip, $original_as)
{
	if ($original_as == 15835)
	{
		$as = 65535;

		if($conn = fsockopen ('whois.cymru.com', 43)) 
		{
			fputs($conn, $ip."\r\n");

			$output = '';

			while(!feof($conn))
			{
				$output .= fgets($conn,128);
			}

			$output = explode("\n", $output);
			if (isset($output[1]))
			{
				$_as = explode("|", $output[1]);
			}
			
			if (isset($_as[0]))
			{
				$as = trim($_as[0]);
				
				if ($as == 'NA')
				{
					$as = 65535;
				}
			}

			fclose($conn);
		}

		return '(' . $ip .') [AS ' . link_as($as) . ']';
	}
	else
	{
		return '(' . $ip .') [AS ' . link_as($original_as) . ']';
	}
}

/**
 * Link to whois IP
 */
function link_whois($line, $name = '')
{
	global $_CONFIG;

	if ($name == '')
	{
		$name = $line;
	}

	return '<a href="'.htmlspecialchars($_CONFIG['ipwhois']).$line.'" target="_blank">'.$name.'</a>';
}

/**
 * Get and print BGP path graph
 */
function get_path_graph($router, $query, $as_pathes, $as_best_path, $format = 'svg')
{
	global $_CONFIG, $_REQUEST;

	$font_size = 9; // default font size
	$graph = new Image_GraphViz();

	$graph->addNode($router, array
	(
		'label' => $_CONFIG['routers'][$router]['description'],
		'shape' => 'box',  
		'style' => 'filled', 
		'fillcolor' => '#FFCCCC', 
		'fontsize' => $font_size + 2
	));

	$query_ptr = get_ptr($query);

	// Draw requested node
	$graph->addNode($query, array
	(
		'URL' => link_command('trace', $query, '', TRUE),
		'target' => '_blank',
		'label' => $query_ptr ? $query."\n".$query_ptr : $query,
		'shape' => 'box',  
		'style' => 'filled',
		'fillcolor' => '#FFFFCC', 
		'fontsize' => $font_size
	));

	$as_pathes_best = $as_list = $as_peer_list = array();

	// Draw path ASs
	foreach ($as_pathes as $as_path_id => $as_path_array)
	{
		$peer_as = reset($as_path_array);

		if (!isset($as_peer_list[$peer_as]) OR $as_peer_list[$peer_as] !== TRUE)
		{
			$as_peer_list[$peer_as] = ($as_best_path === $as_path_id);
		}

		$as_list = array_merge($as_list, $as_path_array);
	}

	$as_list = array_unique($as_list);

	// Draw our AS
	if (!in_array('AS'.$_CONFIG['asn'], $as_list))
	{
		$group_as = 'AS'.$_CONFIG['asn'];
		$group_label = $group_as."\n".$_CONFIG['company'];

		$node_array = array
		(
			'target' => '_blank',
			'label' => $group_label,
			'style' => 'filled',
			'fillcolor' => 'white', 
			'fontsize' => $font_size
		);

		if (isset($_CONFIG['routers'][$router]['group']) AND 
			$_CONFIG['routers'][$router]['group'] !== $_CONFIG['asn'] AND
			$_CONFIG['routers'][$router]['group'] !== $group_as)
		{
			$group_as = 'AS'.ltrim($_CONFIG['routers'][$router]['group'], 'AS');
			$group_label = $_CONFIG['routers'][$router]['group'];

			if ($group_asinfo = get_asinfo($group_as))
			{
				$group_label = isset($group_asinfo['description']) ? $group_as."\n".$group_asinfo['description'] : $group_as;
				$node_array['URL'] = $_CONFIG['aswhois'].$group_as;
			}

			$node_array['label'] = $group_label;
		}
		else
		{
			$node_array['URL'] = $_CONFIG['aswhois'].$group_as;
		}

		$graph->addNode($group_as, $node_array);
		$graph->addEdge(array($group_as => $router), array
		(
			'color' => 'red'
		));
	}

	foreach ($as_list as $as_id)
	{
		$color = isset($as_peer_list[$as_id]) ? ($as_peer_list[$as_id] ? '#CCFFCC' : '#CCCCFF') : 'white';

		$asinfo = get_asinfo($as_id);

		$graph->addNode($as_id, array
		(
			'URL' => $_CONFIG['aswhois'].$as_id,
			'target' => '_blank',
			'label' => isset($asinfo['description']) ? $as_id."\n".$asinfo['description'] : $as_id,
			'style' => 'filled', 
			'fillcolor' => $color, 
			'fontsize' => $font_size
		));
	}

	// Draw pathes
	foreach ($as_pathes as $as_path_id => $as_path_array)
	{
		$first = $last = FALSE;

		foreach ($as_path_array as $as_path)
		{
			if (!$first)
			{
				$first = $last = $as_path;

				continue;
			}

			if (!isset($as_pathes_best[$last.$as_path]))
			{
				$graph->addEdge(array($last => $as_path), array
				(
					'color' => ($as_best_path === $as_path_id) ? 'red' : 'black'
				));

				if ($as_best_path === $as_path_id)
				{
					$as_pathes_best[$last.$as_path] = TRUE;
				}
			}

			$last = $as_path;
		}

		if (!isset($as_pathes_best[$router.$first]))
		{
			$graph->addEdge(array($router => $first), array
			(
				'color' => ($as_best_path === $as_path_id) ? 'red' : 'black'
			));

			if ($as_best_path === $as_path_id)
			{
				$as_pathes_best[$router.$first] = TRUE;
			}
		}
	}

	// Draw last path
	$graph->addEdge(array($as_path => $query), array
	(
		'color' => ($as_pathes_best) ? 'red' : 'black'
	));

	$graph->image($format);

	exit;
}

/**
 * Get and print blank image
 */
function get_blank_graph($string, $format = 'svg')
{
	$graph = new Image_GraphViz();

	$graph->addNode('error', array
	(
		'label' => $string,
		'shape' => 'none',
		'fontcolor' => 'red', 
		'fontsize' => 14
	));

	$graph->image($format);

	exit;
}

/**
 * Get address information from RADb
 */
function get_radb($request)
{
	if (!$fp = @fsockopen('whois.radb.net', 43, $errnum, $errstr, 10))
	{
		return FALSE;
	}

	fputs($fp, $request."\r\n");

	$data = '';

	while (!feof($fp)) 
	{
		$data .= fgets($fp, 128);
	}

	fclose($fp);

	$exp = explode("\n\n", $data);

	if (!isset($exp[0]) OR empty($exp[0]))
	{
		return FALSE;
	}

	$lines = explode("\n", $exp[0]);

	$return = array();

	foreach ($lines as $line)
	{
		$line = explode(':', $line, 2);

		if (isset($line[0]) AND isset($line[1]))
		{
			$return[$line[0]] = trim($line[1]);
		}
	}

	if (sizeof($return) < 1)
	{
		return FALSE;
	}

	return $return;
}

/**
 * Get AS name information from "Team Cymru - IP to ASN Mapping"
 */
function get_asinfo($request)
{
	if (!$dns = dns_get_record($request.'.asn.cymru.com', DNS_TXT) OR !isset($dns[0]['txt']))
	{
		return FALSE;
	}

	$segments = array_map('trim', explode('|', $dns[0]['txt'], 5));

	if (sizeof($segments) != 5)
	{
		return FALSE;
	}

	list($segments[4], $segments[5]) = explode(' ', $segments[4], 2);

	$segments[5] = str_replace('_', '"', $segments[5]);

	return array
	(
		'asn' => $segments[0],
		'country' => $segments[1],
		'registrar' => $segments[2],
		'regdate' => $segments[3],
		'asname' => $segments[4],
		'description' => $segments[5],
	);
}

/**
 * Get PTR record for IPv4/IPv6 address
 */
function get_host($hostname)
{
	global $protocol;

	if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE))
	{
		return $hostname;
	}

	$dns_a = (($dns_a = dns_get_record($hostname, DNS_A)) !== FALSE) ? $dns_a : array();
	$dns_aaaa = (($dns_aaaa = dns_get_record($hostname, DNS_AAAA)) !== FALSE) ? $dns_aaaa : array();

	$ip_array = array();

	foreach (array_merge($dns_aaaa, $dns_a) as $record)
	{
		switch ($record['type'])
		{
			case 'A':
				$ip_array[] = $record['ip'];
				break;

			case 'AAAA':
				$ip_array[] = $record['ipv6'];
				break;

			case 'CNAME':
				$ip_array = array_push($ip_array, get_host($record['target']));
				break;
		}
	}

	$ipv4_array = $ipv6_array = array();

	foreach ($ip_array as $ip)
	{
		if (strpos($ip, ':') !== FALSE)
		{
			$ipv6_array[] = $ip;
		}
		else
		{
			$ipv4_array[] = $ip;
		}
	}

	$ip_array = ($protocol == 'ipv6') 
		? $ipv6_array 
		: $ipv4_array;

	if (!$ipv6_array OR !$ipv4_array)
	{
		$ip_array = array_merge($ipv6_array, $ipv4_array);
	}

	if (!$ip_array OR (!$ipv4_array AND $protocol != 'ipv6'))
	{
		return FALSE;
	}

	return end($ip_array);
}

/**
 * Get PTR record for IPv4/IPv6 address
 */
function get_ptr($ip)
{
	if (preg_match("/:/", $ip))
	{
		$unpack = unpack('H*hex', @inet_pton($ip));
		$arpa = implode('.', array_reverse(str_split($unpack['hex']))).'.ip6.arpa';
	}
	else
	{
		$arpa = implode('.', array_reverse(explode('.', $ip))).'.in-addr.arpa.';
	}

	if (!$arpa OR !$dns = dns_get_record($arpa, DNS_PTR))
	{
		return $ip;
	}

	$ptr = array();

	foreach ($dns as $record) 
	{
		if ($record['type'] == 'PTR') 
		{
			$ptr[] = $record['target'];
		}
	}

	if (!$ptr) 
	{
		return $ip;
	}

	return reset($ptr);
}

/**
 * Group router list
 */
function group_routers($array)
{
	$return = array();

	foreach ($array as $key => $value)
	{
		if (isset($value['group']) AND $value['group'] != '')
		{
			$return[$value['group']][$key] = $value;

			continue;
		}

		$return[''][$key] = $value;
	}

	return $return;
}

// ------------------------------------------------------------------------

/**
* Telnet class
* 
* Used to execute remote commands via telnet connection 
* Usess sockets functions and fgetc() to process result
* 
* All methods throw Exceptions on error
* 
* Written by Dalibor Andzakovic <dali@swerve.co.nz>
* Based on the code originally written by Marc Ennaji and extended by 
* Matthias Blaser <mb@adfinis.ch>
*
* Modified by Dmitry Shin <dmitry.s@hsdn.org>, 2018
*/
class Telnet 
{
	private $host;
	private $port;
	private $timeout;
	
	private $socket  = NULL;
	private $buffer = NULL;
	private $prompt;
	private $errno;
	private $errstr;
	private $header1;
	private $header2;

	public $NULL;
	public $DC1;
	public $WILL;
	public $WONT;
	public $DO;
	public $DONT;
	public $IAC;
	public $LINEMODE;

	const TELNET_ERROR = FALSE;
	const TELNET_OK = TRUE;        
	
	/**
	* Constructor. Initialises host, port and timeout parameters
	* defaults to localhost port 23 (standard telnet port)
	* 
	* @access	public
	* @param	string	$host Host name or IP addres
	* @param	int		$port TCP port number
	* @param	int		$timeout Connection timeout in seconds
	* @return	void
	*/
	public function __construct($host = '127.0.0.1', $port = '23', $timeout = 10, $prompt = '/[^\s]{2,}[\$%>] {0,1}$/')
	{
		$this->default_prompt = $prompt;
		$this->setPrompt();

		$this->host = $host;
		$this->port = $port;
		$this->timeout = $timeout;

		$this->header1 =
			chr(0xFF).chr(0xFB).chr(0x1F).	// 0xFF 0xFB 0x1F - WILL command - NEGOTIATE-WINDOW-SIZE
			chr(0xFF).chr(0xFB).chr(0x20).	// 0xFF 0xFB 0x20 - WILL command - TERMINAL-SPEED
			chr(0xFF).chr(0xFB).chr(0x18).	// 0xFF 0xFB 0x18 - WILL command - TERMINAL-TYPE
			chr(0xFF).chr(0xFB).chr(0x27).	// 0xFF 0xFB 0x27 - WILL command - NEW-ENVIRON
			chr(0xFF).chr(0xFD).chr(0x01).	// 0xFF 0xFD 0x01 - DO command - ECHO
			chr(0xFF).chr(0xFB).chr(0x03).	// 0xFF 0xFB 0x03 - WILL command - SUPPRESS-GO-AHEAD
			chr(0xFF).chr(0xFD).chr(0x03).	// 0xFF 0xFD 0x03 - DO command - SUPPRESS-GO-AHEAD
			chr(0xFF).chr(0xFC).chr(0x23).	// 0xFF 0xFC 0x23 - WON'T command - X-DISPLAY-LOCATION
			chr(0xFF).chr(0xFC).chr(0x24).	// 0xFF 0xFC 0x24 - WON'T command - ENVIRONMENT
			chr(0xFF).chr(0xFA).			// 0xFF 0xFA ... - SB command
								chr(0x1F).chr(0x00).chr(0xA0).chr(0x00).chr(0x18).	// NEGOTIATE-WINDOW-SIZE 
																					// <Width1>=0 <Width0>=160 <Height1>=0 <Height0>=24
			chr(0xFF).chr(0xF0).			// 0xFF 0xF0 - SE command
			chr(0xFF).chr(0xFA).			// 0xFF 0xFA ... - SB command
								chr(0x20).chr(0x00).chr(0x33).chr(0x38).chr(0x34).
								chr(0x30).chr(0x30).chr(0x2C).chr(0x33).chr(0x38).
								chr(0x34).chr(0x30).chr(0x30).	// TERMINAL-SPEED - 38400,38400
			chr(0xFF).chr(0xF0).			// 0xFF 0xF0 - SE command
			chr(0xFF).chr(0xFA).			// 0xFF 0xFA ... - SB command
									chr(0x27).chr(0x00).	// NEW-ENVIRON <IS> <empty>
			chr(0xFF).chr(0xF0).			// 0xFF 0xF0 - SE command
			chr(0xFF).chr(0xFA).			// 0xFF 0xFA ... - SB command
									chr(0x18).chr(0x00).chr(0x58).chr(0x54).chr(0x45).  
									chr(0x52).chr(0x4D).	// TERMINAL-TYPE: <IS> XTERM
			chr(0xFF).chr(0xF0);			// 0xFF 0xF0 - SE command

		$this->header2 = 
			chr(0xFF).chr(0xFC).chr(0x01).	// 0xFF 0xFC 0x01 - WON'T command - ECHO
			chr(0xFF).chr(0xFC).chr(0x22).	// 0xFF 0xFC 0x22 - WON'T command - LINEMODE
			chr(0xFF).chr(0xFE).chr(0x05).	// 0xFF 0xFE 0x05 - DON'T command - STATUS
			chr(0xFF).chr(0xFC).chr(0x21);	// 0xFF 0xFC 0x21 - WON'T command - TOGGLE-FLOW-CONTROL  

		$this->NULL = chr(0);
		$this->DC1 = chr(17);
		$this->WILL = chr(251);
		$this->WONT = chr(252);
		$this->DO = chr(253);
		$this->DONT = chr(254);
		$this->IAC = chr(255);
		$this->LINEMODE = chr(34);

		$this->connect();
	}

	/**
	* Destructor. Cleans up socket connection and command buffer
	* 
	* @access	public
	* @return	void 
	*/
	public function __destruct() 
	{
		$this->disconnect();
		$this->buffer = NULL;
	}

	/**
	* Attempts connection to remote host. Returns TRUE if sucessful.      
	* 
	* @access	public
	* @return	bool
	*/
	public function connect()
	{
		if (!preg_match('/([0-9]{1,3}\\.){3,3}[0-9]{1,3}/', $this->host)) 
		{
			$ip = gethostbyname($this->host);
			
			if ($this->host == $ip)
			{
				throw new Exception("Cannot resolve ".$this->host.".");
			}
			else
			{
				$this->host = $ip; 
			}
		}

		$this->socket = @fsockopen($this->host, $this->port, $this->errno, $this->errstr, $this->timeout);

		$this->write($this->header1, FALSE);

		usleep(100800);

		$this->write($this->header2, FALSE);

		usleep(100800);

		if (!$this->socket)
		{        	
			throw new Exception("Cannot connect to ".$this->host." on port ".$this->port.".");
		}
		
		return self::TELNET_OK;
	}

	/**
	* Closes IP socket
	* 
	* @access	public
	* @return	bool
	*/
	public function disconnect()
	{
		if ($this->socket)
		{
			$this->write('quit');

			if (!fclose($this->socket))
			{
				throw new Exception("Error while closing telnet socket.");                
			}

			$this->socket = NULL;
		}    

		return self::TELNET_OK;
	}
	
	/**
	* Executes command and returns a string with result.
	* This method is a wrapper for lower level private methods
	* 
	* @access	public
	* @param	string		$command Command to execute      
	* @return	string		Command result
	*/
	public function exec($command)
	{
		$this->write($command);
		$this->waitPrompt();

		return $this->getBuffer();
	}

	/**
	* Attempts login to remote host.
	* This method is a wrapper for lower level private methods and should be 
	* modified to reflect telnet implementation details like login/password
	* and line prompts. Defaults to standard unix non-root prompts
	* 
	* @access	public
	* @param	string		$username Username
	* @param	string		$password Password
	* @return	bool 
	*/
	public function login($username = FALSE, $password = FALSE) 
	{
		try
		{
			if ($username)
			{
				$this->setPrompt('/(ogin|name|word):.*$/');
				$this->waitPrompt();
				$this->write($username);
			}

			if ($password)
			{
				$this->setPrompt('/word:.*$/');
				$this->waitPrompt();
				$this->write($password);
			}

			$this->setPrompt();
			$this->waitPrompt();
		}
		catch (Exception $e)
		{
			throw new Exception("Login failed.");
		}

		return self::TELNET_OK;
	}

	/**
	* Sets the string of characters to respond to.
	* This should be set to the last character of the command line prompt
	* 
	* @access	public
	* @param	string		$s String to respond to
	* @return	bool
	*/
	public function setPrompt($s = '')
	{
		$this->prompt = $this->default_prompt;

		if ($s != '')
		{
			$this->prompt = $s;
		}

		return self::TELNET_OK;
	}

	/**
	* Gets character from the socket
	*     
	* @access	public
	* @return	void
	*/
	public function getc() 
	{
		@socket_set_timeout($this->socket, $this->timeout);

		return fgetc($this->socket); 
	}

	/**
	* Clears internal command buffer
	* 
	* @access	public
	* @return	void
	*/
	public function clearBuffer() 
	{
		$this->buffer = '';
	}

	/**
	* Reads characters from the socket and adds them to command buffer.
	* Handles telnet control characters. Stops when prompt is ecountered.
	* 
	* @access	public
	* @param	string		$prompt
	* @return	bool
	*/
	public function readTo($prompt)
	{
		if (!$this->socket)
		{
			throw new Exception("Telnet connection closed.");            
		}

		$this->clearBuffer();

		do
		{
			$c = $this->getc();

			if ($c === false)
			{
				throw new Exception("Couldn't find the requested : '".$prompt."', it was not in the data returned from server.");  
			}

			if ($c == $this->IAC)
			{
				if ($this->negotiateTelnetOptions())
				{
					continue;
				}
			}

			$this->buffer .= $c;

			if (@preg_match($prompt, $this->buffer)) 
			{			
				return self::TELNET_OK;
			}
		}
		while ($c != $this->NULL OR $c != $this->DC1);
	}

	/**
	* Write command to a socket
	* 
	* @access	public
	* @param	string		$buffer Stuff to write to socket
	* @param	bool		$addNewLine Default true, adds newline to the command 
	* @return	bool
	*/
	public function write($buffer = '', $addNewLine = TRUE)
	{
		if (!$this->socket)
		{
			throw new Exception("Telnet connection closed.");
		}

		// clear buffer from last command
		$this->clearBuffer();

		if ($addNewLine == true)
		{
			$buffer .= "\r\n";
		}

		if (!fwrite($this->socket, $buffer) < 0)
		{
			throw new Exception("Error writing to socket.");
		}

		return self::TELNET_OK;
	}
	
	/**
	* Returns the content of the command buffer
	* 
	* @access	public
	* @return	string		Content of the command buffer 
	*/
	public function getBuffer()
	{
		return $this->buffer;
	}
	
	/**
	* Telnet control character magic
	* 
	* @access	public
	* @param	string		$command Character to check
	* @return	bool
	*/
	public function negotiateTelnetOptions()
	{
		$c = $this->getc();
	
		if ($c != $this->IAC)
		{
			if (($c == $this->DO) OR ($c == $this->DONT))
			{
				$opt = $this->getc();
				fwrite($this->socket, $this->IAC.$this->WONT.$opt);
			}
			else if (($c == $this->WILL) OR ($c == $this->WONT)) 
			{
				$opt = $this->getc();            
				fwrite($this->socket, $this->IAC.$this->DONT.$opt);
			}
		}
		else
		{
			throw new Exception('Error: Something Wicked Happened.');
		}

		return self::TELNET_OK;
	}

	/**
	* Reads socket until prompt is encountered
	*
	* @access	public
	*/
	public function waitPrompt()
	{
		return $this->readTo($this->prompt);
	}

} // class Telnet

/* End of file */
