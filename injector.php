<?php
/**
 * @package com_meego_ratings
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Injector
 *
 * @package com_meego_ratings
 */
class com_meego_ratings_injector
{
    public function inject_template(midgardmvc_core_request $request)
    {
        $request->add_component_to_chain(midgardmvc_core::get_instance()->component->get('com_meego_ratings'), true);

        try
        {
            $this->language = midgardmvc_core::get_instance()->configuration->get('default_language');
            if (! $this->language)
            {
              $this->language = 'en_US';
            }

            midgardmvc_core::get_instance()->i18n->set_language($this->language, false);
        }
        catch (Exception $e)
        {
            echo $e;
        }
    }
}
?>