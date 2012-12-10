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
    private $connected = false;

    private function get_connected()
    {
        $mvc = midgardmvc_core::get_instance();

        $language = $mvc->configuration->get('default_language');
        if (! $language)
        {
          $language = 'en_US';
        }
        $mvc->i18n->set_language($language, false);

        midgard_object_class::connect_default('com_meego_comments_comment', 'action-deleted', array('com_meego_ratings_injector', 'delete_rating_handler'));

        $this->connected = true;
    }

    public function inject_template(midgardmvc_core_request $request)
    {
        if (! $this->connected)
        {
            self::get_connected();
        }
        $mvc = midgardmvc_core::get_instance();
        $request->add_component_to_chain($mvc->component->get('com_meego_ratings'), true);
    }

    /**
     * A signal handler for deleting comments
     * Checks if the comment was attached to a rating object
     * If the rating object has no rating, then it will delete this object
     *
     * @param object com_meego_comments_comment object
     */
    public static function delete_rating_handler(com_meego_comments_comment $comment)
    {
        $mvc = midgardmvc_core::get_instance();

        if ($comment)
        {
            $storage = new midgard_query_storage('com_meego_ratings_rating');
            $q = new midgard_query_select($storage);

            $qc = new midgard_query_constraint_group('AND');

            $qc->add_constraint(new midgard_query_constraint(
                new midgard_query_property('comment'),
                '=',
                new midgard_query_value($comment->id)
            ));
            $qc->add_constraint(new midgard_query_constraint(
                new midgard_query_property('rating'),
                '=',
                new midgard_query_value('')
            ));

            $q->set_constraint($qc);

            $q->execute();
            $ratings = $q->list_objects();

            if (count($ratings))
            {
                foreach ($ratings as $rating)
                {
                    $res = $rating->delete();
                    if ($res)
                    {
                        $mvc->log(__CLASS__, 'Rating object with id: ' . $rating->id . ' has been successfuly deleted.', 'info');
                    }
                    else
                    {
                        $mvc->log(__CLASS__, 'Rating object with id: ' . $rating->id . ' could not be deleted.', 'info');
                    }
                }
            }
        }
    }
}
?>
