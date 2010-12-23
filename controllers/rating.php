<?php
class com_meego_ratings_controllers_rating extends midgardmvc_core_controllers_baseclasses_crud
{
    private $relocate = null;

    // the maximum rating one can give to an object
    // @todo: make it configurable
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
        $field = $this->form->add_field('rating', 'integer');
        $field->set_value(3);
        if ($this->object->rating > 0)
        {
            $field->set_value($this->object->rating);
        }

        $widget = $field->set_widget('starrating');
        // @todo: get the rating options from configuration
        $widget->add_option('Very bad', 1);
        $widget->add_option('Poor', 2);
        $widget->add_option('Average', 3);
        $widget->add_option('Good', 4);
        $widget->add_option('Excellent', 5);
        //$widget->set_placeholder('Rate here');

        $field = $this->form->add_field('comment', 'text');
        $field->set_value('');
        $widget = $field->set_widget('textarea');
    }

    /**
     * Processing the POSTed form.
     * Makes sure that rating value is within accepted range.
     * If comment also submitted then it creates a new comment object,
     * gets the ID of the comment object and assigns it to the rating object.
     *
     */
    public function process_form()
    {
        $this->form->process_post();

        // make sure rating is within range
        $this->object->rating = $this->form->rating->get_value();

        if ($this->object->rating > $this->maxrate)
        {
            $this->object->rating = $this->maxrate;
        }

        // if comment is also given then create a new comment entry
        if (isset($this->form->comment))
        {
            $comment = $this->form->comment->get_value();
            if (strlen($comment))
            {
                $obj = new com_meego_comments_comment();
                $obj->to = $this->object->to;
                $obj->content = $comment;
                $obj->create();
                if ($obj->id)
                {
                    $this->object->comment = $obj->id;
                    var_dump($this->object->comment);
                }
            }
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

        $this->data['ratings'] = array();
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
        $ratings = $q->list_objects();

        $sum = 0;
        if (count($ratings))
        {
            $this->data['rated'] = true;
            foreach ($ratings as $rating)
            {
                $sum += $rating->rating;
                if ($rating->ratingcomment)
                {
                    $comment = new com_meego_comments_comment($rating->ratingcomment);
                    $rating->ratingcommentcontent = $comment->content;
                }
                array_push($this->data['ratings'], $rating);
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

        // @todo: can't add elements to head from here.. why?

        // Enable jQuery in case it is not enabled yet
        midgardmvc_core::get_instance()->head->enable_jquery();

        // Add rating CSS
        $css = array
        (
            'href' => MIDGARDMVC_STATIC_URL . '/com_meego_ratings/js/jquery.rating/jquery.rating.css',
            'rel' => 'stylesheet'
        );
        midgardmvc_core::get_instance()->head->add_link($css);
        // Add rating js
        midgardmvc_core::get_instance()->head->add_jsfile(MIDGARDMVC_STATIC_URL . '/com_meego_ratings/js/jquery.rating/jquery.rating.pack.js', true);
    }
}