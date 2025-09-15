<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;


/**
 * Class WordpressExporterHelperPlugin
 * @package Grav\Plugin
 */
class WordpressExporterHelperPlugin extends Plugin
{

    /** @var array */
    public $features = [
        'blueprints' => 0, // Use priority 0
    ];

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized' => [
                ['onPluginsInitialized', 0]
            ]
        ];
    }

    /**
     * Composer autoload
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized(): void
    {
        // Don't proceed if we are in the admin plugin.
        if ( $this->isAdmin()) {
            $this->enable([
                'onGetPageBlueprints' => ['onGetPageBlueprints', 0],
            ]);
            return;
        }

       $this->enable([
            'onGetPageTemplates' => ['onGetPageTemplates', 0],
        ]);

        // Wordpress plain permalinks are based on their ID numbers (e.g. `?p=123`).
        if ($this->grav['uri']->query("p")) {
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 0]
            ]);
        }
    }

    /**
     * Add blueprint directory.
     */
    public function onGetPageBlueprints(Event $event): void
    {
        $types = $event->types;
        $types->scanBlueprints('plugin://' . $this->name . '/blueprints');
    }

    /**
     * Add templates directory.
     */
    public function onGetPageTemplates(Event $event): void
    {
        $types = $event->types;
        $types->scanTemplates('plugin://' . $this->name . '/templates');
    }

    /**
     * Redirect user to a page with the correct WordPress permalink data.
     */
    public function onPageInitialized(): void
    {
        $permalink_query = $this->grav['uri']->query("p");
        $pages = $this->grav['pages'];
        foreach ($pages->all() as $page) {
            $header = $page->header();
            if (isset($header->wp)) {
                if ($header->wp['post']['ID'] == $permalink_query){
                    $new_route = $page->route();
                    unset($this->grav['page']);
                    $this->grav['page'] = $page;
                }
            }
        }
    }
}
