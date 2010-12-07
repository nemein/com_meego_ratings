<?php
class com_meego_ratings_controllers_list
{
    public function __construct(midgardmvc_core_request $request)
    {
        $this->request = $request;
    }

    public function get_ratings(array $args)
    {
        $this->data['to'] = midgard_object_class::get_object_by_guid($args['to']);
        if (!$this->data['to'])
        {
            throw new midgardmvc_exception_notfound("rating target not found");
        }

        $this->data['ratings'] = array();

        $storage = new midgard_query_storage('com_meego_ratings_rating');
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

        $q->add_order(new midgard_query_property('metadata.created', $storage), SORT_ASC);
        $q->execute();
        $ratings = $q->list_objects();

        foreach ($ratings as $rating)
        {
            $this->data['ratings'][] = $rating;
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