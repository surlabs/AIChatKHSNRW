<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';


/**
 *  This file is part of the AI Chat Repository Object plugin for ILIAS, which allows your platform's users
 *  To connect with an external LLM service
 *  This plugin is created and maintained by SURLABS.
 *
 *  The AI Chat Repository Object plugin for ILIAS is open-source and licensed under GPL-3.0.
 *  For license details, visit https://www.gnu.org/licenses/gpl-3.0.en.html.
 *
 *  To report bugs or participate in discussions, visit the Mantis system and filter by
 *  the category "AI Chat" at https://mantis.ilias.de.
 *
 *  More information and source code are available at:
 *  https://github.com/surlabs/AIChat
 *
 *  If you need support, please contact the maintainer of this software at:
 *  info@surlabs.es
 *
 */

/**
 * Class ilAIChatPlugin
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 */
class ilAIChatPlugin extends ilRepositoryObjectPlugin
{
    const PLUGIN_ID = 'xaic';

    const PLUGIN_NAME = 'AIChat';
    protected function uninstallCustom(): void
    {
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }
    public function allowCopy(): bool
    {
        return true;
    }
}
