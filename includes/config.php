<?php
/**
 * SITE SETTINGS. Edit values here and every page, the footer, the contact
 * form recipient and the WhatsApp button update automatically.
 */
return [
  'site_name'  => 'Cala Laiya',
  'tagline'    => 'Beachfront resort in San Juan, Batangas',
  'description'=> 'A beachfront resort in a quiet cove, 2.5 hours south of Manila. Casitas, two pools, balsa raft, kayaking, weddings and corporate events.',
  'email'      => 'reservations@calalaiya.ph',   // also receives contact form inquiries
  'phone'      => '(+63) 917-8839900',           // as displayed
  'phone_tel'  => '+639178839900',               // for tel: links
  'whatsapp'   => '639178839900',                // digits only, country code first, no + or spaces (PLACEHOLDER: confirm)
  'address'    => ['Sitio Marulas, Laiya Ibabao, San Juan', 'Batangas City, Philippines 4226'],
  'check_in'   => '2:00 PM',                     // PLACEHOLDER: confirm with the resort
  'check_out'  => '12:00 PM',                    // PLACEHOLDER: confirm with the resort
  'dining_hours' => ['Breakfast'=>'7:00 AM to 10:00 AM','Lunch'=>'11:00 AM to 3:00 PM','Dinner at La Mensa'=>'5:30 PM to 9:30 PM'], // PLACEHOLDERS: confirm
  'site_url'   => (string)env('APP_URL', ''),     // set APP_URL in .env (used for links in emails)
  'max_room_images' => 10,                       // photos allowed per room gallery
  'currency'   => '₱',
  'social'     => ['facebook' => '#', 'instagram' => '#'],
];
