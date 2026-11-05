<?php
// Placeholder content. Replace with DB queries (PDO) once the backend is ready.
return [
  // units = how many of this room type the resort has. Availability = units minus overlapping bookings.
  // Amenities, sizes and prices are PLACEHOLDERS: confirm with the resort.
  'rooms' => [
    ['id'=>'casita3','name'=>'Three-bedroom Casita','blurb'=>'Two-storey beachfront house with a living area and a roof terrace, built for the whole family.','guests'=>8,'units'=>2,'price'=>18500,'img'=>'casita.jpg','featured'=>true,'beds'=>'3 bedrooms','amenities'=>['Roof terrace','Living and dining area','Beachfront views','Welcome drink on arrival']],
    ['id'=>'poolside','name'=>'Poolside Villa','blurb'=>'Quiet, shaded and a few steps from the pool.','guests'=>4,'units'=>4,'price'=>9800,'img'=>'pool.jpg','beds'=>'2 bedrooms','amenities'=>['Steps from the pool','Shaded terrace','Large windows','Welcome drink on arrival']],
    ['id'=>'seaview','name'=>'Seaview Room','blurb'=>'Balcony facing the cove, made for sunrise coffee.','guests'=>2,'units'=>6,'price'=>6900,'img'=>'room-seaview.webp','beds'=>'1 bedroom','amenities'=>['Balcony facing the cove','Sunrise views','Quiet corner','Welcome drink on arrival']],
  ],
  'experiences' => [
    ['Balsa raft','Free for guests. Best at sunset.','hero-raft.jpg'],
    ['Two swimming pools','Clean, new and open all day.','pool.jpg'],
    ['Kayaking and paddle board','Explore the cove at your own pace.',null],
    ['Snorkeling and boat tours','Banca trips to nearby reefs.',null],
    ['Banana boat and speed boat','For groups who want some noise.',null],
    ['Kids activities','Games, sand and pool time.',null],
    ['Archery and lawn games','Open grounds, pet-friendly.',null],
  ],
  // SAMPLE REVIEWS: ratings and dates were generated at random as placeholders.
  // Replace with real, consented guest reviews before launch.
  'reviews' => [
    ['name'=>'Marco L.','stay'=>'Corporate retreat','rating'=>5,'date'=>'October 2026','title'=>'Good for a team retreat','text'=>'We held a two-day retreat here. Meetings in the morning, kayaking in the afternoon, and the team came back relaxed.'],
    ['name'=>'Rafael D.','stay'=>'Friends','rating'=>5,'date'=>'October 2026','title'=>'Calm cove, fun on the water','text'=>'Kayaks and paddle boards in the calm cove were great. The restaurant gets busy when the resort is full, so order early.'],
    ['name'=>'Maria S.','stay'=>'Family stay','rating'=>5,'date'=>'September 2026','title'=>'Room for everyone','text'=>'We booked the three-bedroom casita for a family weekend. Plenty of space, beachfront views, and the kids were in the pool all day.'],
    ['name'=>'Denise T.','stay'=>'Traveling with a dog','rating'=>5,'date'=>'May 2026','title'=>'Our dog had the lawn to himself','text'=>'We brought our dog and he loved the grass and the sand. The staff were relaxed about it and helpful.'],
    ['name'=>'Kristine P.','stay'=>'Wedding guest','rating'=>4,'date'=>'May 2026','title'=>'A beautiful beach wedding','text'=>'The setting under the trees was lovely and the staff kept the day running smoothly.'],
    ['name'=>'Paolo M.','stay'=>'Barkada trip','rating'=>5,'date'=>'February 2026','title'=>'The boodle fight was the highlight','text'=>'Seafood boodle fight, banana boat and speed boat. A great mix for a big group of friends.'],
    ['name'=>'Joel and Anna R.','stay'=>'Couple','rating'=>4,'date'=>'November 2025','title'=>'The sunset raft ride','text'=>'Quiet, unhurried and easy. We took the balsa raft out at sunset and it was the best hour of the trip.'],
    ['name'=>'Bea N.','stay'=>'Family with a toddler','rating'=>5,'date'=>'November 2025','title'=>'Clean pools, good breakfast','text'=>'The pools were clean and the little ones loved the shallow area. Breakfast had plenty of Filipino options.'],
    ['name'=>'Grace V.','stay'=>'With parents','rating'=>4,'date'=>'June 2025','title'=>'Peaceful for my parents','text'=>'My parents enjoyed the calm grounds and the friendly staff, who checked on them often.'],
    ['name'=>'Ian C.','stay'=>'Weekend getaway','rating'=>5,'date'=>'April 2025','title'=>'Lovely, with some works ongoing','text'=>'A lovely place and an easy escape from Manila. A few areas were still being renovated during our stay, but it did not spoil the weekend.'],
  ],
];
