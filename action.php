<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

/**
 * DokuWiki Plugin emailpolicy (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Phil Underwood <beardydoc@gmail.com>
 */
class action_plugin_emailpolicy extends ActionPlugin {
    /** @inheritDoc */
    public function register(EventHandler $controller): void {
        $controller->register_hook('AUTH_USER_CHANGE', 'BEFORE', $this, 'handleUserChange');
    }

    /**
     * Event handler for AUTH_USER_CHANGE
     *
     * @see https://www.dokuwiki.org/devel:event:auth_userdata_change
     * @param Event $event Event object
     * @param mixed $param optional parameter passed when event was registered
     * @return void
     */
    public function handleUserChange(Event $event, $param): void {
        $email = $this->extractEmail($event);

        if (is_null($email))
            return;


        $deny = $this->getConf('deny');
        if ($this->checkEmail($email, $deny)) {
            msg($this->getLang('deny_failed') . $deny);
            $event->preventDefault();
            $event->stopPropagation();
        }

        $allow = $this->getConf('allow');
        if ($allow !== '' && !$this->checkEmail($email, $allow)) {
            msg($this->getLang('allow_failed') . $allow);
            $event->preventDefault();
            $event->stopPropagation();
        }
    }

    protected function extractEmail(Event $event): ?string {
        return match ($event->data['type']) {
            'create' => $event->data['params'][3],
            'modify' => $event->data['params'][1]['mail'],
            _ => null,
        };
    }

    protected function checkEmail(string $email, string $domains): bool {
        $email_domain = substr(strrchr($email, '@'), 1);
        $domains = array_map('trim', explode(',', $domains));
        return in_array($email_domain, $domains);
    }
}
