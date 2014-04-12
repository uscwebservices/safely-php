<?php

    $event = array(
    "event_id" => 906300,
    "title" => null,
    "subtitle" => "USC School of Cinematic Arts",
    "summary" => null,
    "description" => null,
    "cost" => "Free",
    "organizer" => "aago@cinema.usc.edu",
    "contact_phone" => "(213) 740-2330",
    "contact_email" => "",
    "rsvp_email" => "",
    "rsvp_url" => "http:\/\/cinema.usc.edu\/events\/reservation.cfm?id=13787",
    "website_url" => "http:\/\/cinema.usc.edu\/events\/event.cfm?id=13787",
    "ticket_url" => "",
    "campus" => "",
    "venue" => "The Albert and Dana Broccoli Theatre, SCA 112, George Lucas Buil",
    "building_code" => "",
    "room" => "SCA 112",
    "address" => "",
    "feature_candidate" => 0,
    "user_id" => 0,
    "username" => "guest",
    "display_name" => "guest",
    "scratch_pad" => "",
    "created" => "2013-10-10 16:16:01",
    "updated" => "2013-10-14 12:33:20",
    "publication_date" => null,
    "calendar_id" => 32,
    "parent_calendar_id" => 32,
    "parent_calendar" => "USC Public Events"
	);

    $sql = 'INSERT INTO events (event_id, title, subtitle, summary, ' .
        'description, ' .
        'venue, campus, building_code, room, address, cost, ' .
        'organizer, contact_phone, contact_email, ' .
        'rsvp_email, rsvp_url, website_url, ticket_url, feature_candidate, ' .
        'user_id, username, display_name, parent_calendar_id, parent_calendar, ' .
        'scratch_pad, created, updated, publication_date) VALUES (' .
        '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '. 
        '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ' .
        '?, ?, ?, ?, ?, ?, ?)';
    $isOK = $db->executeSQL($sql, array(
        $event['event_id'],
        $event['title'],
        $event['subtitle'],
        $event['summary'],
        $event['description'],
        $event['venue'],
        $event['campus'],
        $event['building_code'],
        $event['room'],
        $event['address'],
        $event['cost'],
        
        $event['organizer'],
        $event['contact_phone'],
        $event['contact_email'],
        $event['rsvp_email'],
        $event['rsvp_url'],
        $event['website_url'],
        $event['ticket_url'],
        $event['feature_candidate'],
        $event['user_id'],
        $event['username'],
        
        $event['display_name'],
        $event['parent_calendar_id'],
        $event['parent_calendar'],
        $event['scratch_pad'],
        $event['created'],
        $event['updated'],
        $event['publication_date']), $debug);
    // $isOK should file in unfix version (it is not failing clearly)
    // $isOK should be true in fixed version.

