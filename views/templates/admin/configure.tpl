{*
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='cronberry' mod='cronberry'}</h3>
	<p>
		<strong>{l s='This is cronberry module for sending cart, order and user info to cronberry dashboard along with firebase config and announcement' mod='cronberry'}</strong><br />
	</p>
	{if $multistore }
	<p>
		{l s='Add followinddg rule to .htaacess or http conf file' mod='cronberry'}<br />
		<pre>{l s='RewriteRule ^firebase-messaging-sw.js$ /modules/cronberryIntegration/views/js/%{HTTP_HOST}-firebase-messaging-sw.js [L]' mod='cronberry'}</pre>
	</p>
	{/if}
	<p>
		{l s='Add following to your cron job scheduler' mod='cronberry'}<br />
		<pre>*/10 * * * * {$shopName}{l s='/module/cronberryIntegration/cronjob?ajax=1' mod='cronberry'}</pre>
	</p>
</div>

