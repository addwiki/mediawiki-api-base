==================
Multipart requests
==================

The MultipartRequest class can be used if you need a FluentRequest that has more parameters to be set on individual parts of a multipart request.

The name is a slight misnomer, because either of the other two Request classes (SimpleRequest and FluentRequest)
will also end up being multipart requests if you pass any parameters of type Resource_.

.. _Resource: http://php.net/manual/en/resource.php

To use a MultipartRequest you must first set the main parameters, and then you can add additional "multipart parameters" to any of the parameters you've set.
(You will get an Exception if you try to set a multipart parameter for a main parameter that doesn't exist yet.)

For example, to add a ``Content-Disposition`` header to a parameter named ``param1``::

    $contentDisposition = 'form-data; name="param1"; filename="a_filename.png"';
    $request = MultipartRequest::factory()
        ->setParams( [ 'param1' => 'Lorem ipsum' ] )
        ->setAction( 'actionname' )
        ->setMultipartParams( [
            'param1' => [
                'headers' => [ 'Content-Disposition' => $contentDisposition ],
            ],
        ] );
    $response = $api->postRequest( $request );

(For details of creating the ``$api`` object in this example, see :ref:`quickstart`.)
