<?php
/**
 * Default homepage class serving as the entry point for public website access.
 * Renders the initial landing page as configured in the framework settings.
 */
class Welcome extends Trongate {

    /**
     * Renders the (default) under-construction homepage for public access.
     *
     * @return void
     */
    public function index(): void {
        $data = [
            'view_module' => 'welcome',
            'view_file' => 'default_homepage'
        ];

        $this->templates->public($data);
    }

    /**
     * Renders the component showcase page.
     * Reachable at /welcome/components in all environments.
     *
     * @return void
     */
    public function components(): void {
        $data = [
            'view_module' => 'welcome',
            'view_file' => 'components_showcase'
        ];

        $this->templates->public($data);
    }

}
