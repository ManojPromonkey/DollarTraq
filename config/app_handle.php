<?php

return [
    
    /*
    Backend
        Baseurl: http://127.0.0.1:3000/
        API path: api/
        Handle Prefix: handle/
        Endpoint: backend/visa/countries/list
    */

	
    /* Blog articles */
	'cms/blog/articles' => [
		'type' => 'list',
        	'auth' => false,
		'model' => 'App\Models\Core\Blogs\BlogArticlesModel',
        //'query_method' => ['model' => 'App\Models\CMS\CMSBlogArticles', 'method' => 'blog_articles']
	],

	'cms/blogs/articles/load' => [
		'type' => 'single',
        'auth' => false,
		'model' => 'App\Models\Core\Blogs\BlogArticlesModel',
		'id' => ['input' => 'slug', 'field' => 'slug'],
	],

	'cms/blogs/articles/preview' => [
		'type' => 'single',
        	'auth' => false,
		'model' => 'App\Models\Core\Blogs\BlogArticlesModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
	],


    /* Application */
    'app/countries/country_states/load' => [
		'type' => 'datasets',
        	'auth' => false,
		'models' => [
			'country_codes' => ['model' => 'App\Models\misc\countries', 'method' => 'country_states'],
		]
	],

	'app/shipment/listing/init' => [
		'type' => 'datasets',
        	'auth' => false,
		'models' => [
			'country_codes' => ['model' => 'App\Models\misc\countries', 'method' => 'country_codes'],
			'timezones' => ['model' => 'App\Models\misc\countries', 'method' => 'timezones'],

			'shipment_carriers' => ['model' => 'App\Models\Shipments\ShipmentsCarriersModel', 'key' => 'row_id', 'value' => 'title'],
			'tracking_methods' => ['model' => 'App\Models\Shipments\ShipmentsTrackingMethodsModel', 'key' => 'row_id', 'value' => 'title'],

			'status' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'status'],
			'status_colors' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'status_colors'],
		]
	],

	'app/shipment/control_tower' => [
		'type' => 'custom',
		'auth' => false,
		// 'model' => 'App\Models\Shipments\ShipmentsModel',
		// 'query_method' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'control_tower_listing'],
		// 'fields' => [
		// 	'exclude' => ['id']
		// ]

		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'control_tower_listing'], // Optional for custom query
	],

	'app/shipment/load_search' => [
		// 'type' => 'list',
		// 'auth' => false,
		// 'model' => 'App\Models\Shipments\ShipmentsModel',
		// 'query_method' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'load_search_listing'],
		// 'fields' => [
		// 	'exclude' => ['id']
		// ]

		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'load_search_listing'],
	],

	'app/shipment/add/init' => [
		'type' => 'datasets',
		//'auth' => false,
		'models' => [
			'shipment_carriers' => ['model' => 'App\Models\Shipments\ShipmentsCarriersModel', 'key' => 'row_id', 'value' => 'title'],
			'tracking_methods' => ['model' => 'App\Models\Shipments\ShipmentsTrackingMethodsModel', 'key' => 'row_id', 'value' => 'title'],
			'country_codes' => ['model' => 'App\Models\misc\Countries', 'method' => 'country_codes'],
			'timezones' => ['model' => 'App\Models\misc\Countries', 'method' => 'timezones'],
			'track_days' => ['model' => 'App\Models\Shipments\ShipmentsUpdatesModel', 'method' => 'track_days'],
			'track_time' => ['model' => 'App\Models\Shipments\ShipmentsUpdatesModel', 'method' => 'track_time'],
			'stop_types' => ['model' => 'App\Models\Shipments\ShipmentsStopsModel', 'method' => 'stop_types'],
			'countries' => ['model' => 'App\Models\misc\Countries', 'method' => 'countries_list'],
			'shipments_count' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'shipments_count'],
		]
	],

	'app/shipment/save/shipment_summary' => [
		'type' => 'save',
		'model' => 'App\Models\Shipments\ShipmentsModel',
		'add_success_message' => 'Shipment has been saved successfully.',
		'update_success_message' => 'Shipment has been updated successfully.',
		'after_callback' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'shipment_save_after'],
		'before_callback' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'shipment_save_before']
	],

	'app/shipment/load/shipment_summary' => [
		'type' => 'custom',
		'auth' => false,
		'model' => 'App\Models\Shipments\ShipmentsModel',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'load_shipment'], // Optional for custom query
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
	],


	/*Trip Stops*/
	'app/shipment/list/shipment_trip_stop' => [
		'type' => 'list',
		'model' => 'App\Models\Shipments\ShipmentsStopsModel',
		'fields' => [
			'exclude' => ['id']
		]
	],

	'app/shipment/load/shipment_trip_stop' => [
		'type' => 'single',
		'model' => 'App\Models\Shipments\ShipmentsStopsModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
	],

	'app/shipment/save/shipment_trip_stop' => [
		'type' => 'save',
		'model' => 'App\Models\Shipments\ShipmentsStopsModel',
		'add_success_message' => 'Stop has been saved successfully.',
		'update_success_message' => 'Stop has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\Shipments\ShipmentsStopsModel', 'method' => 'shipment_stop_save_before']
	],

	'app/shipment/remove/shipment_trip_stop' => [
        'type' => 'delete',
        'model' => 'App\Models\Shipments\ShipmentsStopsModel',
	   'id' => ['input' => 'row_id', 'field' => 'row_id'],
        'success_message' => 'Stop has been removed successfully.',
		// 'after_callback' => ['model' => 'professionals/professionals_model', 'method' => 'profile_after_remove_action']
    ],

	'app/action_centre/init' => [
		'type' => 'datasets',
		'models' => [
			'shipments_carriers' => ['model' => 'App\Models\Shipments\ShipmentsCarriersModel', 'key' => 'row_id', 'value' => 'title'],
			'tracking_methods' => ['model' => 'App\Models\Shipments\ShipmentsTrackingMethodsModel', 'key' => 'row_id', 'value' => 'title'],
			'status' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'status'],
			'status_colors' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'status_colors'],
		]
	],


    'app/action_centre/list' => [
		//'type' => 'list',
		'auth' => false,
		//'model' => 'App\Models\Shipments\ShipmentsActionCentreModel',
		// 'query_method' => ['model' => 'App\Models\Shipments\ShipmentsActionCentreModel', 'method' => 'list'],
		// 'fields' => [
		// 	'exclude' => ['id']
		// ]

		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsActionCentreModel', 'method' => 'list'],
	],

	'app/profile/carriers/shortlisted/save' => [
		'type' => 'save',
		'model' => 'App\Models\Customers\CustomersCarriersShortlistedModel',
		
		'add_success_message' => 'Shortlisted Carrier has been saved successfully.',
		'update_success_message' => 'Shortlisted Carrier has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\Customers\CustomersCarriersShortlistedModel', 'method' => 'carrier_shortlisted_before']
	],

	'app/profile/carriers/shortlisted/list' => [
		'type' => 'custom',		
		//'model' => 'App\Models\Customers\CustomersCarriersShortlistedModel',
		'model' => ['model' => 'App\Models\Customers\CustomersCarriersShortlistedModel', 'method' => 'shortlisted_list'],
		//'query_method' => ['model' => 'App\Models\Customers\CustomersCarriersShortlistedModel', 'method' => 'shortlisted_list'],
	],

	'app/profile/carriers/shortlisted/remove' => [
		'type' => 'delete',
		'model' => 'App\Models\Customers\CustomersCarriersShortlistedModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
		'success_message' => 'Shortlisted has been removed successfully.',
    ],

    'app/customer/roles/init' => [
		'type' => 'datasets',
		'auth' => false,
		'models' => [
			'roles' => ['model' => 'App\Models\Customers\CustomersUsersRolesModel', 'key' => 'row_id', 'value' => 'role_title'],
		]
	],
    
	'app/profile/load' => [
		'type' => 'single',
		'model' => 'App\Models\Customers\CustomersModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
	],

	'app/customer/load' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'load_customer'], // Optional for custom query
	],

	'app/profile/update' => [
		'type' => 'save',
		'model' => 'App\Models\Customers\CustomersModel',
		'add_success_message' => 'Profile has been saved successfully.',
		'update_success_message' => 'Profile has been updated successfully.',
		'after_callback' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'after_profile_update'],
	],

	'app/profile/update/password' => [ 
		'type' => 'custom',
        	'model' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'update_password'],
	],


	////
	'app/customer/answer_types/init' => [
		'type' => 'datasets',
		'auth' => false,
		'models' => [
			'answer_types' => ['model' => 'App\Models\Customers\CustomersQuestionsModel', 'method' => 'answer_types'],
		]
	],

	'app/customer/question/save' => [
		'type' => 'save',
		'model' => 'App\Models\Customers\CustomersQuestionsModel',
		
		'add_success_message' => 'Carrier Question has been saved successfully.',
		'update_success_message' => 'Carrier Question has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\Customers\CustomersQuestionsModel', 'method' => 'question_save_before']
	],

	'app/customer/question/list' => [
		'type' => 'list',
		'model' => 'App\Models\Customers\CustomersQuestionsModel',
		'query_method' => ['model' => 'App\Models\Customers\CustomersQuestionsModel', 'method' => 'get_questions'],
		'fields' => [
			'exclude' => ['id']
		]
	],

	'app/customer/question/remove' => [
		'type' => 'delete',
		'model' => 'App\Models\Customers\CustomersQuestionsModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
		'success_message' => 'Question has been removed successfully.',
    ],
	////

	'app/shipment/address/convert' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'address_convert'],
	],

	'app/shipment/single' => [
		'type' => 'custom',
		'auth' => false,
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'single_view'],
	],


	'app/shipment/tracking/pulses' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'tracking_pulses'],
	],

	'app/shipment/trip_stops/update_sort_order' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'update_sort_order'],
	],

	'app/shipment/carriers' => [
		'type' => 'list',
		'auth' => false,
		'model' => 'App\Models\Shipments\ShipmentsCarriersModel',
		'fields' => [
			'exclude' => ['id']
		]
	],

	'app/shipment/carriers/single' => [
		'type' => 'single',
		'model' => 'App\Models\Shipments\ShipmentsCarriersModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
	],

	'app/shipment/carriers/save' => [
		'type' => 'save',
		'model' => 'App\Models\Shipments\ShipmentsCarriersModel',
		
		'add_success_message' => 'Shipment carrier has been saved successfully.',
		'update_success_message' => 'Shipment carrier has been updated successfully.',
	],

	'app/users' => [
		'type' => 'list',
		'model' => 'App\Models\Customers\CustomersModel',
		'query_method' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'sub_users_list'],
		'fields' => [
			'exclude' => ['id']
		]
	],

	'app/users/unique/email' => [
        'type' => 'unique',
        'model' => 'App\Models\Customers\CustomersModel',
        'field' => ['key' => 'email', 'field' => 'email'],
        'row_id' => 'row_id',
    ],

	'app/users/unique/mobile' => [
		'type' => 'unique',
		'model' => 'App\Models\Customers\CustomersModel',
		
		'field' => ['key' => 'contact', 'field' => 'contact'],
		'row_id' => 'row_id'
	],

	'app/users/save' => [
		'type' => 'save',
		'model' => 'App\Models\Customers\CustomersModel',
		
		'add_success_message' => 'Shipment carrier has been saved successfully.',
		'update_success_message' => 'Shipment carrier has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'user_save_before']
	],

	'app/users/invite' => [
		'type' => 'save',
		'model' => 'App\Models\Customers\CustomersModel',
		
		'add_success_message' => 'Shipment carrier has been saved successfully.',
		'update_success_message' => 'Shipment carrier has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'user_invite_before'],
		'after_callback' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'user_invite_after']
	],

	'app/users/single' => [
		'type' => 'single',
		'model' => 'App\Models\Customers\CustomersModel',
		
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
	],


    	/* Frontend */
	'contact/query/save' => [
        'type' => 'save',
		'model' => 'App\Models\Leads\QueriesModel',
		'auth' => false,
		'success_message' => 'Your query has been submitted successfully.',
		'after_callback' => ['model' => 'App\Models\Leads\QueriesModel', 'method' => 'after_contact_query']
    	],

	///account
	'app/account/auth/login' => [ 
		'type' => 'custom',
		'auth' => false,
        	'model' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'customer_login'],
	],

	'customer/account/signup' => [
        'type' => 'save',
		'model' => 'App\Models\Customers\CustomersModel',
		'auth' => false,
		'success_message' => 'Your account has been created successfully.',
		'before_callback' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'before_signup'],
		'after_callback' => ['model' => 'App\Models\Customers\CustomersModel', 'method' => 'after_signup']
    ],


    'app/subscription/intent' => [ ///pending testing
		'type' => 'custom',
        	'model' => ['model' => 'App\Models\Customers\CustomersSubscriptionsModel', 'method' => 'subscription_intent'],
	],


	'app/support/init' => [
		'type' => 'datasets',
		'auth' => false,
		'models' => [
			'priority_list' => ['model' => 'App\Models\Support\SupportTicketsModel', 'method' => 'priority_list'],
			'status_list' => ['model' => 'App\Models\Support\SupportTicketsModel', 'method' => 'status_list'],
		]
	],

	'app/support/ticket/save' => [
        	'type' => 'save',
		'model' => 'App\Models\Support\SupportTicketsModel',
		'success_message' => 'Your ticket has been submitted successfully.',
		'before_callback' => ['model' => 'App\Models\Support\SupportTicketsModel', 'method' => 'before_save_ticket'],
		'after_callback' => ['model' => 'App\Models\Support\SupportTicketsModel', 'method' => 'after_save_ticket']
    	],

	'app/support/ticket/list' => [
		'type' => 'list',		
		'model' => 'App\Models\Support\SupportTicketsModel',
		'query_method' => ['model' => 'App\Models\Support\SupportTicketsModel', 'method' => 'customer_ticket_list'],
	],

	'app/support/ticket/load' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Support\SupportTicketsModel', 'method' => 'ticket_view'],
    ],

    'app/support/ticket/messages/save' => [
        	'type' => 'save',
		'model' => 'App\Models\Support\SupportTicketsMssagesModel',
		'success_message' => 'Ticket Mssages has been submitted successfully.',
		'before_callback' => ['model' => 'App\Models\Support\SupportTicketsMssagesModel', 'method' => 'before_save_messages'],
		'after_callback' => ['model' => 'App\Models\Support\SupportTicketsMssagesModel', 'method' => 'after_save_messages']
    	],


    	/* Drivers */
	'driver/account/update' => [
		'type' => 'save',
		'model' => 'App\Models\Drivers\DriversModel',
		'fields' => [
			'exclude' => ['id']
		]
	],

	'driver/loads/list' => [ /// pending for list
		//'type' => 'list', //query_method
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'loads_list'],
		'fields' => [
			'exclude' => ['id']
		]
	],


	///drivers
	'drivers/account/update' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'drivers_update'],
	],

	'drivers/auth/login' => [ 
		'type' => 'custom',
		'auth' => false,
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'drivers_login'],
	],

	'drivers/auth/init' => [ 
		'type' => 'datasets',
		'auth' => false,
		'models' => [
			'country_codes' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'country_codes'],
		]
	],

	'drivers/auth/otp/send' => [ 
		'type' => 'custom',
		'auth' => false,
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'otp_send'],
	],

	'drivers/auth/otp/verify_otp' => [ 
		'type' => 'custom',
		'auth' => false,
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'verify_otp'],
	],

	'drivers/auth/signout' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'logout'],
	],


	'drivers/auth/signup' => [ 
		'type' => 'custom',
		'auth' => false,
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'drivers_signup'],
	],

	'imageuploader' => [
        'type' => 'upload',
	   'auth' => false,
        'file' => 'image',
        'path' => 'uploads/profile_pic',
        'success_message' => 'File saved successfully.',
    ],

	'drivers/init' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'loads_init'],
	],

	'drivers/loads/list' => [ 
		'type' => 'custom',
		'model' => 'App\Models\Drivers\DriversModel',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'loads_list'],
	],

	'drivers/messages/fetch' => [ 
		'type' => 'custom',
		'model' => 'App\Models\Chats\ChatsModel',
		'model' => ['model' => 'App\Models\Chats\ChatsModel', 'method' => 'chats_fetch'],
	],

	'drivers/messages/list' => [ 
		'type' => 'custom',
		'model' => 'App\Models\Chats\ChatsModel',
		'model' => ['model' => 'App\Models\Chats\ChatsModel', 'method' => 'chats_list'],
	],
	
	'drivers/messages/send' => [ 
		'type' => 'custom',
		'model' => 'App\Models\Chats\ChatsModel',
		'model' => ['model' => 'App\Models\Chats\ChatsModel', 'method' => 'chats_send'],
	],

	'drivers/request/send' => [ 
		'type' => 'custom',
		'model' => 'App\Models\Shipments\ShipmentsActionCentreModel',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsActionCentreModel', 'method' => 'request_send'],
	],

	'drivers/shipment/accept' => [ 
		'type' => 'custom',
		'model' => 'App\Models\Drivers\DriversModel',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'shipment_accept'],
	],

	'drivers/shipment/documents' => [ 
		'type' => 'custom',
		'model' => 'App\Models\Drivers\DriversModel',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'shipment_documents'],
	],

	'drivers/shipment/timeline' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Drivers\DriversModel', 'method' => 'shipment_timeline'],
	],

	'drivers/tracking/document/remove' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsDocuments', 'method' => 'tracking_document_remove'],
	],

	'drivers/tracking/document/upload' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsDocuments', 'method' => 'tracking_document_upload'],
	],

	'drivers/tracking/pulse' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Tracking\ShipmentTrackingPulseModel', 'method' => 'drivers_tracking_pulse'],
	],

	'drivers/tracking/update' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Tracking\ShipmentTrackingPulseModel', 'method' => 'drivers_tracking_update'],
	],


	'shipments/load' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'shipment_load'],
	],

	'shipments/single' => [ 
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'single_single'],
	],

	

	/* backend */
	'backend/shipments/save' => [
		'type' => 'save',
		'model' => 'App\Models\Shipments\ShipmentsModel',
		'add_success_message' => 'Shipment has been created successfully.',
		'update_success_message' => 'Shipment has been updated successfully.'
	],

	'backend/shipments/load' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'shipment_load'],
	],

	'backend/shipments/data' => [
		'type' => 'single',
		'model' => 'App\Models\Shipments\ShipmentsModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
		'fields' => [
			'exclude' => ['id']
		],
	],

	'backend/shipments/unique_shipment_number' => [
		'type' => 'unique',
		'model' => 'App\Models\Shipments\ShipmentsModel',
		'field' => ['key' => 'shipment_number', 'field' => 'shipment_number'],
			'row_id' => 'row_id'
	],


	'backend/shipment/control_tower' => [
		// 'type' => 'list',
		// 'model' => 'App\Models\Shipments\ShipmentsModel',
		// 'query_method' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'control_tower_listing'],
		// 'fields' => [
		// 	'exclude' => ['id']
		// ]
		
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'control_tower_listing'], // Optional for custom query
	],

	'backend/shipment/load_search' => [
		// 'type' => 'list',
		// 'model' => 'App\Models\Shipments\ShipmentsModel',
		// 'query_method' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'load_search_listing'],
		// 'fields' => [
		// 	'exclude' => ['id']
		// ]

		'type' => 'custom',
		'model' => ['model' => 'App\Models\Shipments\ShipmentsModel', 'method' => 'load_search_listing'],
	],

	'backend/customers/list' => [
		'type' => 'list',
		'model' => 'App\Models\Customers\CustomersModel',
	],

	'backend/customers/update' => [
		'type' => 'save',
		'model' => 'App\Models\Customers\CustomersModel',
		'add_success_message' => 'Customer has been created successfully.',
		'update_success_message' => 'Customer has been updated successfully.'
	],

	'backend/customers/load' => [
		'type' => 'single',
		'model' => 'App\Models\Customers\CustomersModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
		'fields' => [
			'exclude' => ['id']
		],
	],

	'backend/queries/init' => [
		'type' => 'datasets',
		'models' => [
			'country_codes' => ['model' => 'App\Models\Leads\QueriesModel', 'method' => 'country_codes'],
			'statuses' => ['model' => 'App\Models\Leads\QueriesModel', 'method' => 'statuses'],
			'page_sources' => ['model' => 'App\Models\Leads\QueriesModel', 'method' => 'page_sources'],
			'query_sources' => ['model' => 'App\Models\Leads\QueriesModel', 'method' => 'query_sources'],
		]
	],

	'backend/queries/list' => [
		'type' => 'list',
		'model' => 'App\Models\Leads\QueriesModel',
	],

	'backend/queries/update' => [
		'type' => 'save',
		'model' => 'App\Models\Leads\QueriesModel',
		'add_success_message' => 'Query has been created successfully.',
		'update_success_message' => 'Query has been updated successfully.',
		'after_callback' => ['model' => 'App\Models\Leads\QueriesModel', 'method' => 'after_query_update']
	],
	
	'backend/queries/load' => [
		'type' => 'single',
		'model' => 'App\Models\Leads\QueriesModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
		'fields' => [
			'exclude' => ['id']
		],
	],

	'backend/query/convert/customer' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Leads\QueriesModel', 'method' => 'convert_customer'],
	],

	'backend/subscription/plans/list' => [
		'type' => 'list',
		'model' => 'App\Models\Subscriptions\SubscriptionPlansModel',
	],

	///error Qus?
	'backend/subscription/plans/add' => [
		'type' => 'save',
		'model' => 'App\Models\Subscriptions\SubscriptionPlansModel',
		'add_success_message' => 'Subscription plan has been created successfully.',
		'update_success_message' => 'Subscription plan has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\Subscriptions\SubscriptionPlansModel', 'method' => 'add_stripe_plan']
	],

	'backend/subscription/plans/load' => [
		'type' => 'single',
		'model' => 'App\Models\Subscriptions\SubscriptionPlansModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
		'fields' => [
			'exclude' => ['id']
		],
	],

	'backend/cms/emails/listing' => [
		'type' => 'list',
		'model' => 'App\Models\CMS\CMSEmailModel',
	],

	'backend/cms/emails/code/unique' => [
        'type' => 'unique',
        'model' => 'App\Models\CMS\CMSEmailModel',
        'field' => ['key' => 'code', 'field' => 'code'],
        'row_id' => 'row_id'
    ],

    'backend/cms/emails/load' => [
        'type' => 'single',
        'model' => 'App\Models\CMS\CMSEmailModel',
    ],

	'backend/cms/emails/save' => [
		'type' => 'save',
		'model' => 'App\Models\CMS\CMSEmailModel',
		'add_success_message' => 'Email template has been created successfully.',
		'update_success_message' => 'Email template has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\CMS\CMSEmailModel', 'method' => 'before_template_save']
	],

	'backend/cms/blog/categories/single' => [
		'type' => 'single',
		'model' => 'App\Models\CMS\BlogsCategoriesModel',
	],

	'backend/cms/blog/categories/save' => [
		'type' => 'save',
		'model' => 'App\Models\CMS\BlogsCategoriesModel',
		'add_success_message' => 'Category has been created successfully.',
		'update_success_message' => 'Category has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\CMS\BlogsCategoriesModel', 'method' => 'before_category_save'],
		'after_callback' => ['model' => 'App\Models\CMS\BlogsCategoriesModel', 'method' => 'after_category_save']
	],

	'backend/cms/blog/categories/remove' => [
		'type' => 'remove',
		'model' => 'App\Models\CMS\BlogsCategoriesModel',
		'id' => ['input' => 'row_id', 'field' => 'row_id'],
		'success_message' => 'Category has been removed successfully.',
		'after_callback' => ['model' => 'App\Models\CMS\BlogsCategoriesModel', 'method' => 'category_remove_after']
    ],

	'backend/cms/blogs/articles/init' => [
		'type' => 'datasets',
		'models' => [
			'statuses' => ['model' => 'App\Models\CMS\CMSBlogArticles', 'method' => 'status_options']
		]
	],

	'backend/cms/blogs/articles' => [
		'type' => 'list',
		'model' => 'App\Models\CMS\CMSBlogArticles',
		'query_method' => ['model' => 'App\Models\CMS\CMSBlogArticles', 'method' => 'blog_articles']
	],

	'backend/cms/blogs/articles/remove' => [
        'type' => 'remove',
        'model' => 'App\Models\CMS\CMSBlogArticles',
        'id' => ['input' => 'row_id', 'field' => 'row_id'],
        'success_message' => 'Article has been removed successfully.',
		'after_callback' => ['model' => 'App\Models\CMS\CMSBlogArticles', 'method' => 'article_remove_after']
    ],
	
	'backend/cms/blogs/article/save' => [
		'type' => 'save',
		'model' => 'App\Models\CMS\CMSBlogArticles',
		'add_success_message' => 'Article has been created successfully.',
		'update_success_message' => 'Acticle has been updated successfully.',
		'before_callback' => ['model' => 'App\Models\CMS\CMSBlogArticles', 'method' => 'before_article_save'],
		'after_callback' => ['model' => 'App\Models\CMS\CMSBlogArticles', 'method' => 'after_article_save']
	],

	'backend/cms/blogs/authors' => [
		'type' => 'list',
		'model' => 'App\Models\CMS\CMSBlogAuthors'
	],
	
	'backend/cms/blogs/authors/remove' => [
        'type' => 'remove',
        'model' => 'App\Models\CMS\CMSBlogAuthors',
        'id' => ['input' => 'row_id', 'field' => 'row_id'],
        'success_message' => 'Author has been removed successfully.',
		'after_callback' => ['model' => 'App\Models\CMS\CMSBlogAuthors', 'method' => 'author_remove_after']
    ],

	'backend/cms/blogs/authors/save' => [
		'type' => 'save',
		'model' => 'App\Models\CMS\CMSBlogAuthors',
		'add_success_message' => 'Author has been created successfully.',
		'update_success_message' => 'Author has been updated successfully.'
	],

	'backend/cms/blogs/authors/single' => [
		'type' => 'single',
		'model' => 'App\Models\CMS\CMSBlogAuthors',
	],

	


	//////motive
	'motive/callback' => [
		'type' => 'custom',
		'auth' => false,
		'model' => ['model' => 'App\Models\Motive\MotiveModel', 'method' => 'callback'],
	],



	/*
	Carriers connect
	*/
	'backend/carriers/connect/request' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'send_request'],
	],

	'backend/carriers/connect/load' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'connect_request_carrier'],
	],

	'backend/carriers/connect/sms/send' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'send_otp'],
	],

	'backend/carriers/connect/sms/verify' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'verify_otp'],
	],

	'backend/carriers/didit/auth' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Auth\DiditModel', 'method' => 'generate_session'],
	],

	'backend/carriers/didit/verify' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Auth\DiditModel', 'method' => 'verify_didit'],
	],

	'backend/carrier/stripe/connect' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'connect_stripe_express_account'],
	],

	'backend/carrier/stripe/verify' => [
		'type' => 'custom',
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'verify_stripe_account'],
	],


	//////Broker
	'broker/carrier/connect/list' => [
		'type' => 'list',		
		'model' => 'App\Models\Connect\CarrierConnectRequestsModel',
		'query_method' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'broker_carrier_connect_list'],
	],

	'broker/carrier/connect/exportCsv' => [
		'type' => 'custom',		
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'exportconnect_listCsv'],
	],

	'broker/carrier/connect/exportPDF' => [
		'type' => 'custom',		
		'model' => ['model' => 'App\Models\Connect\CarrierConnectRequestsModel', 'method' => 'exportConnect_listPdf'],
	],
];