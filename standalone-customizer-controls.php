<?php
/**
 * Plugin name: Standalone Customizer Controls
 * Description: Use Customizer controls in contexts outside of the Customizer itself.
 * Author: Weston Ruter, XWP
 * Version: 0.1
 * Author: XWP
 * Author URI: https://xwp.co/
 * License: GPLv2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Copyright (c) 2016 XWP (https://xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package StandaloneCustomizerControls
 */

namespace StandaloneCustomizerControls;

require_once __DIR__ . '/class-plugin.php';
$standalone_customizer_controls_plugin = new Plugin();
add_action( 'plugins_loaded', array( $standalone_customizer_controls_plugin, 'add_hooks' ) );
