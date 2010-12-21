<?php
class com_meego_ratings_controllers_rating extends midgardmvc_core_controllers_baseclasses_crud
{
    private $relocate = null;

    // the maximum rating one can give to an object
    // @todo: how to make it configurable?
    private $maxrate = 5;

    /**
     * @todo: docs
     */
    public function load_object(array $args)
    {
        if (array_key_exists('rating', $args))
        {
            $this->object = new com_meego_ratings_rating($args['rating']);
        }
    }

    /**
     * @todo: docs
     */
    public function prepare_new_object(array $args)
    {
        $this->data['parent'] = midgard_object_class::get_object_by_guid($args['to']);
        $this->object = new com_meego_ratings_rating();
        $this->object->to = $this->data['parent']->guid;
    }

    /**
     * @todo: docs
     */
    public function load_form()
    {
        $this->form = midgardmvc_helper_forms::create('com_meego_ratings_rating');
        $this->form->set_action
        (
            midgardmvc_core::get_instance()->dispatcher->generate_url
            (
                'rating_create', array
                (
                    'to' => $this->data['parent']->guid
                ),
                $this->request
            )
        );

        if ($this->request->is_subrequest())
        {
            // rating posting form is in a dynamic_load, set parent URL for redirects
            $root_request = midgardmvc_core::get_instance()->context->get_request(0);
            $field = $this->form->add_field('relocate', 'text', false);
            $field->set_value($root_request->get_path());
            $field->set_widget('hidden');
        }

        // Basic element information
        $field = $this->form->add_field('rating', 'text');
        $field->set_value($this->object->rating);

        // @todo: use a different widget
        $widget = $field->set_widget('textarea');
        $widget->set_placeholder('Rate here');
    }

    /**
     * @todo: docs
     */
    public function process_form()
    {
        $this->form->process_post();
        $this->object->rating = $this->form->rating->get_value();

        if ($this->object->rating > $this->maxrate)
        {
            $this->object->rating = $this->maxrate;
        }

        if (isset($this->form->relocate))
        {
            $this->relocate = $this->form->relocate->get_value();
        }
    }

    /**
     * @todo: docs
     */
    public function get_url_read()
    {
        return midgardmvc_core::get_instance()->dispatcher->generate_url
        (
            'list_ratings', array
            (
                'to' => $this->object->to
            ),
            $this->request
        );
    }

    /**
     * @todo: docs
     */
    public function get_url_update()
    {
        return midgardmvc_core::get_instance()->dispatcher->generate_url
        (
            'rating_update', array
            (
                'rating' => $this->object->guid
            ),
            $this->request
        );
    }

    /**
     * @todo: docs
     */
    public function relocate_to_read()
    {
        if (!is_null($this->relocate))
        {
            midgardmvc_core::get_instance()->head->relocate($this->relocate);
        }
        midgardmvc_core::get_instance()->head->relocate($this->get_url_read());
    }

    /**
     * Retrieves all ratings belonging to the object having the guid: $this->data['to'].
     *
     * Passes all ratings to the view ($this->data['ratings']).
     * Calcualtes the average rating and passes that to the view too ($this->data['average']).
     * Sets the rated flag ($this->data['rated']) to show if object was ever rated or not.
     * Sets the can_post flag ($this->data['can_post']), so that the view can determine
     * whether to show a POST form or not.
     *
     * @param array arguments
     */
    public function get_ratings(array $args)
    {
        $this->data['to'] = midgard_object_class::get_object_by_guid($args['to']);

        if ( ! $this->data['to'] )
        {
            throw new midgardmvc_exception_notfound("rating target not found");
        }

        parent::get_read($args);

        $this->data['average'] = false;
        $this->data['rated'] = false;

        $storage = new midgard_query_storage('com_meego_ratings_rating_author');
        $q = new midgard_query_select($storage);
        $q->set_constraint
        (
            new midgard_query_constraint
            (
                new midgard_query_property('to', $storage),
                '=',
                new midgard_query_value($this->data['to']->guid)
            )
        );

        $q->add_order(new midgard_query_property('posted', $storage), SORT_ASC);
        $q->execute();
        $this->data['ratings'] = $q->list_objects();

        $sum = 0;
        if (count($this->data['ratings']))
        {
            $this->data['rated'] = true;
            foreach ($this->data['ratings'] as $rating)
            {
                $sum += $rating->rating;
            }
            $this->data['average'] = round($sum / count($this->data['ratings']), 1);
        }

        if (midgardmvc_core::get_instance()->authentication->is_user())
        {
            $this->data['can_post'] = true;
        }
        else
        {
            $this->data['can_post'] = false;
        }
    }
}