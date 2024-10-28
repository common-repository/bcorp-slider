<?php

class BCorp_Slider_Data {
  public function __construct ()
  {
    $this->add_shortcodes();
  }

  private function add_shortcodes () {
    $GLOBALS['bcorp_shortcodes_data']->bcorp_add_shortcode_var(
    'bcorp_slider_transitions',array(
      'fade_twins'=>'Fade Twins','rotate_overlap'=>'Rotate Overlap','switch'=>'Switch','rotate_relay'=>'Rotate Relay','doors'=>'Doors',
      'rotate_in_plus_out_minus'=>'Rotate in+ out-','fly_twins'=>'Fly Twins','rotate_in_minus_out_plus'=>'Rotate in- out+',
      'rotate_axis_up_overlap'=>'Rotate Axis up overlap','chess_replace_tb'=>'Chess Replace TB','chess_replace_lr'=>'Chess Replace LR',
      'shift_tb'=>'Shift TB','shift_lr'=>'Shift LR','return_tb'=>'Return TB','return_lr'=>'Return LR','rotate_axis_down'=>'Rotate Axis down',
      'extrude_replace'=>'Extrude Replace','fade'=>'Fade','fade_in_l'=>'Fade in L','fade_in_r'=>'Fade in R','fade_in_t'=>'Fade in T',
      'fade_in_b'=>'Fade in B','fade_in_lr'=>'Fade in LR','fade_in_lr_chess'=>'Fade in LR Chess','fade_in_tb'=>'Fade in TB',
      'fade_in_tb_chess'=>'Fade in TB Chess','fade_in_corners'=>'Fade in Corners','fade_out_l'=>'Fade out L','fade_out_r'=>'Fade out R',
      'fade_out_t'=>'Fade out T','fade_out_b'=>'Fade out B','fade_out_lr'=>'Fade out LR','fade_out_lr_chess'=>'Fade out LR Chess',
      'fade_out_tb'=>'Fade out TB','fade_out_tb_chess'=>'Fade out TB Chess','fade_out_corners'=>'Fade out Corners',
      'fade_fly_in_l'=>'Fade Fly in L','fade_fly_in_r'=>'Fade Fly in R','fade_fly_in_t'=>'Fade Fly in T','fade_fly_in_b'=>'Fade Fly in B',
      'fade_fly_in_lr'=>'Fade Fly in LR','fade_fly_in_lr_chess'=>'Fade Fly in LR Chess','fade_fly_in_tb'=>'Fade Fly in TB',
      'fade_fly_in_tb_chess'=>'Fade Fly in TB Chess','fade_fly_in_corners'=>'Fade Fly in Corners','fade_fly_out_l'=>'Fade Fly out L',
      'fade_fly_out_r'=>'Fade Fly out R','fade_fly_out_t'=>'Fade Fly out T','fade_fly_out_b'=>'Fade Fly out B',
      'fade_fly_out_lr'=>'Fade Fly out LR','fade_fly_out_lr_chess'=>'Fade Fly out LR Chess','fade_fly_out_tb'=>'Fade Fly out TB',
      'fade_fly_out_tb_chess'=>'Fade Fly out TB Chess','fade_fly_out_corners'=>'Fade Fly out Corners','fade_clip_in_h'=>'Fade Clip in H',
      'fade_clip_in_v'=>'Fade Clip in V','fade_clip_out_h'=>'Fade Clip out H','fade_clip_out_v'=>'Fade Clip out V','fade_stairs'=>'Fade Stairs',
      'fade_random'=>'Fade Random','fade_swirl'=>'Fade Swirl','fade_zigzag'=>'Fade ZigZag','swing_outside_in_stairs'=>'Swing Outside in Stairs',
      'swing_outside_in_zigzag'=>'Swing Outside in ZigZag','swing_outside_in_swirl'=>'Swing Outside in Swirl',
      'swing_outside_in_random'=>'Swing Outside in Random','swing_outside_in_random_chess'=>'Swing Outside in Random Chess',
      'swing_outside_in_square'=>'Swing Outside in Square','swing_outside_out_stairs'=>'Swing Outside out Stairs',
      'swing_outside_out_zigzag'=>'Swing Outside out ZigZag','swing_outside_out_swirl'=>'Swing Outside out Swirl',
      'swing_outside_out_random'=>'Swing Outside out Random','swing_outside_out_random_chess'=>'Swing Outside out Random Chess',
      'swing_outside_out_square'=>'Swing Outside out Square','swing_inside_in_stairs'=>'Swing Inside in Stairs',
      'swing_inside_in_zigzag'=>'Swing Inside in ZigZag','swing_inside_in_swirl'=>'Swing Inside in Swirl',
      'swing_inside_in_random'=>'Swing Inside in Random','swing_inside_in_random_chess'=>'Swing Inside in Random Chess',
      'swing_inside_in_square'=>'Swing Inside in Square','swing_inside_out_zigzag'=>'Swing Inside out ZigZag',
      'swing_inside_out_swirl'=>'Swing Inside out Swirl','dodge_dance_outside_in_stairs'=>'Dodge Dance Outside in Stairs',
      'dodge_dance_outside_in_swirl'=>'Dodge Dance Outside in Swirl','dodge_dance_outside_in_zigzag'=>'Dodge Dance Outside in ZigZag',
      'dodge_dance_outside_in_random'=>'Dodge Dance Outside in Random','dodge_dance_outside_in_random_chess'=>'Dodge Dance Outside in Random Chess',
      'dodge_dance_outside_in_square'=>'Dodge Dance Outside in Square','dodge_dance_outside_out_stairs'=>'Dodge Dance Outside out Stairs',
      'dodge_dance_outside_out_swirl'=>'Dodge Dance Outside out Swirl','dodge_dance_outside_out_zigzag'=>'Dodge Dance Outside out ZigZag',
      'dodge_dance_outside_out_random'=>'Dodge Dance Outside out Random','dodge_dance_outside_out_random_chess'=>'Dodge Dance Outside out Random Chess',
      'dodge_dance_outside_out_square'=>'Dodge Dance Outside out Square','dodge_dance_inside_in_stairs'=>'Dodge Dance Inside in Stairs',
      'dodge_dance_inside_in_swirl'=>'Dodge Dance Inside in Swirl','dodge_dance_inside_in_zigzag'=>'Dodge Dance Inside in ZigZag',
      'dodge_dance_inside_in_random'=>'Dodge Dance Inside in Random','dodge_dance_inside_in_random_chess'=>'Dodge Dance Inside in Random Chess',
      'dodge_dance_inside_in_square'=>'Dodge Dance Inside in Square','dodge_dance_inside_out_stairs'=>'Dodge Dance Inside out Stairs',
      'dodge_dance_inside_out_swirl'=>'Dodge Dance Inside out Swirl','dodge_dance_inside_out_zigzag'=>'Dodge Dance Inside out ZigZag',
      'dodge_dance_inside_out_random'=>'Dodge Dance Inside out Random','dodge_dance_inside_out_random_chess'=>'Dodge Dance Inside out Random Chess',
      'dodge_dance_inside_out_square'=>'Dodge Dance Inside out Square','dodge_pet_outside_in_stairs'=>'Dodge Pet Outside in Stairs',
      'dodge_pet_outside_in_swirl'=>'Dodge Pet Outside in Swirl','dodge_pet_outside_in_zigzag'=>'Dodge Pet Outside in ZigZag',
      'dodge_pet_outside_in_random'=>'Dodge Pet Outside in Random','dodge_pet_outside_in_random_chess'=>'Dodge Pet Outside in Random Chess',
      'dodge_pet_outside_in_square'=>'Dodge Pet Outside in Square','dodge_pet_outside_out_stairs'=>'Dodge Pet Outside out Stairs',
      'dodge_pet_outside_out_swirl'=>'Dodge Pet Outside out Swirl','dodge_pet_outside_out_zigzag'=>'Dodge Pet Outside out ZigZag',
      'dodge_pet_outside_out_random'=>'Dodge Pet Outside out Random','dodge_pet_outside_out_random_chess'=>'Dodge Pet Outside out Random Chess',
      'dodge_pet_outside_out_square'=>'Dodge Pet Outside out Square','dodge_pet_inside_in_stairs'=>'Dodge Pet Inside in Stairs',
      'dodge_pet_inside_in_swirl'=>'Dodge Pet Inside in Swirl','dodge_pet_inside_in_zigzag'=>'Dodge Pet Inside in ZigZag',
      'dodge_pet_inside_in_random'=>'Dodge Pet Inside in Random','dodge_pet_inside_in_random_chess'=>'Dodge Pet Inside in Random Chess',
      'dodge_pet_inside_in_square'=>'Dodge Pet Inside in Square','dodge_pet_inside_out_stairs'=>'Dodge Pet Inside out Stairs',
      'dodge_pet_inside_out_swirl'=>'Dodge Pet Inside out Swirl','dodge_pet_inside_out_zigzag'=>'Dodge Pet Inside out ZigZag',
      'dodge_pet_inside_out_random'=>'Dodge Pet Inside out Random','dodge_pet_inside_out_random_chess'=>'Dodge Pet Inside out Random Chess',
      'dodge_pet_inside_out_square'=>'Dodge Pet Inside out Square','dodge_outside_out_stairs'=>'Dodge Outside out Stairs',
      'dodge_outside_out_swirl'=>'Dodge Outside out Swirl','dodge_outside_out_zigzag'=>'Dodge Outside out ZigZag',
      'dodge_outside_out_random'=>'Dodge Outside out Random','dodge_outside_out_random_chess'=>'Dodge Outside out Random Chess',
      'dodge_outside_out_square'=>'Dodge Outside out Square','dodge_outside_in_stairs'=>'Dodge Outside in Stairs',
      'dodge_outside_in_swirl'=>'Dodge Outside in Swirl','dodge_outside_in_zigzag'=>'Dodge Outside in ZigZag',
      'dodge_outside_in_random'=>'Dodge Outside in Random','dodge_outside_in_random_chess'=>'Dodge Outside in Random Chess',
      'dodge_outside_in_square'=>'Dodge Outside in Square','dodge_inside_out_stairs'=>'Dodge Inside out Stairs',
      'dodge_inside_out_swirl'=>'Dodge Inside out Swirl','dodge_inside_out_zigzag'=>'Dodge Inside out ZigZag',
      'dodge_inside_out_random'=>'Dodge Inside out Random','dodge_inside_out_random_chess'=>'Dodge Inside out Random Chess',
      'dodge_inside_out_square'=>'Dodge Inside out Square','dodge_inside_in_stairs'=>'Dodge Inside in Stairs',
      'dodge_inside_in_swirl'=>'Dodge Inside in Swirl','dodge_inside_in_zigzag'=>'Dodge Inside in ZigZag',
      'dodge_inside_in_random'=>'Dodge Inside in Random','dodge_inside_in_random_chess'=>'Dodge Inside in Random Chess',
      'dodge_inside_in_square'=>'Dodge Inside in Square','dodge_inside_in_tl'=>'Dodge Inside in TL','dodge_inside_in_tr'=>'Dodge Inside in TR',
      'dodge_inside_in_bl'=>'Dodge Inside in BL','dodge_inside_in_br'=>'Dodge Inside in BR','dodge_inside_out_tl'=>'Dodge Inside out TL',
      'dodge_inside_out_tr'=>'Dodge Inside out TR','dodge_inside_out_bl'=>'Dodge Inside out BL','dodge_inside_out_br'=>'Dodge Inside out BR',
      'flutter_outside_in'=>'Flutter Outside in','flutter_outside_in_wind'=>'Flutter Outside in Wind',
      'flutter_outside_in_swirl'=>'Flutter Outside in Swirl','flutter_outside_in_column'=>'Flutter Outside in Column',
      'flutter_outside_out'=>'Flutter Outside out','flutter_outside_out_wind'=>'Flutter Outside out Wind',
      'flutter_outside_out_swirl'=>'Flutter Outside out Swirl','flutter_outside_out_column'=>'Flutter Outside out Column',
      'flutter_inside_in'=>'Flutter Inside in','flutter_inside_in_wind'=>'Flutter Inside in Wind',
      'flutter_inside_in_swirl'=>'Flutter Inside in Swirl','flutter_inside_in_column'=>'Flutter Inside in Column',
      'flutter_inside_out'=>'Flutter Inside out','flutter_inside_out_wind'=>'Flutter Inside out Wind',
      'flutter_inside_out_swirl'=>'Flutter Inside out Swirl','flutter_inside_out_column'=>'Flutter Inside out Column',
      'rotate_vdouble_plus_in'=>'Rotate VDouble+ in','rotate_hdouble_plus_in'=>'Rotate HDouble+ in','rotate_vdouble_minus_in'=>'Rotate VDouble- in',
      'rotate_hdouble_minus_in'=>'Rotate HDouble- in','rotate_vdouble_plus_out'=>'Rotate VDouble+ out',
      'rotate_hdouble_plus_out'=>'Rotate HDouble+ out','rotate_vdouble_minus_out'=>'Rotate VDouble- out',
      'rotate_hdouble_minus_out'=>'Rotate HDouble- out','rotate_vfork_plus_in'=>'Rotate VFork+ in','rotate_hfork_plus_in'=>'Rotate HFork+ in',
      'rotate_vfork_plus_out'=>'Rotate VFork+ out','rotate_hfork_plus_out'=>'Rotate HFork+ out','rotate_zoom_plus_in'=>'Rotate Zoom+ in',
      'rotate_zoom_plus_in_l'=>'Rotate Zoom+ in L','rotate_zoom_plus_in_r'=>'Rotate Zoom+ in R','rotate_zoom_plus_in_t'=>'Rotate Zoom+ in T',
      'rotate_zoom_plus_in_b'=>'Rotate Zoom+ in B','rotate_zoom_plus_in_tl'=>'Rotate Zoom+ in TL','rotate_zoom_plus_in_tr'=>'Rotate Zoom+ in TR',
      'rotate_zoom_plus_in_bl'=>'Rotate Zoom+ in BL','rotate_zoom_plus_in_br'=>'Rotate Zoom+ in BR','rotate_zoom_plus_out'=>'Rotate Zoom+ out',
      'rotate_zoom_plus_out_l'=>'Rotate Zoom+ out L','rotate_zoom_plus_out_r'=>'Rotate Zoom+ out R','rotate_zoom_plus_out_t'=>'Rotate Zoom+ out T',
      'rotate_zoom_plus_out_b'=>'Rotate Zoom+ out B','rotate_zoom_plus_out_tl'=>'Rotate Zoom+ out TL','rotate_zoom_plus_out_tr'=>'Rotate Zoom+ out TR',
      'rotate_zoom_plus_out_bl'=>'Rotate Zoom+ out BL','rotate_zoom_plus_out_br'=>'Rotate Zoom+ out BR','rotate_zoom_minus_in'=>'Rotate Zoom- in',
      'rotate_zoom_minus_in_l'=>'Rotate Zoom- in L','rotate_zoom_minus_in_r'=>'Rotate Zoom- in R','rotate_zoom_minus_in_t'=>'Rotate Zoom- in T',
      'rotate_zoom_minus_in_b'=>'Rotate Zoom- in B','rotate_zoom_minus_in_tl'=>'Rotate Zoom- in TL','rotate_zoom_minus_in_tr'=>'Rotate Zoom- in TR',
      'rotate_zoom_minus_in_bl'=>'Rotate Zoom- in BL','rotate_zoom_minus_in_br'=>'Rotate Zoom- in BR','rotate_zoom_minus_out'=>'Rotate Zoom- out',
      'rotate_zoom_minus_out_l'=>'Rotate Zoom- out L','rotate_zoom_minus_out_r'=>'Rotate Zoom- out R','rotate_zoom_minus_out_t'=>'Rotate Zoom- out T',
      'rotate_zoom_minus_out_b'=>'Rotate Zoom- out B','rotate_zoom_minus_out_tl'=>'Rotate Zoom- out TL',
      'rotate_zoom_minus_out_tr'=>'Rotate Zoom- out TR','rotate_zoom_minus_out_bl'=>'Rotate Zoom- out BL',
      'rotate_zoom_minus_out_br'=>'Rotate Zoom- out BR','zoom_vdouble_plus_in'=>'Zoom VDouble+ in','zoom_hdouble_plus_in'=>'Zoom HDouble+ in',
      'zoom_vdouble_minus_in'=>'Zoom VDouble- in','zoom_hdouble_minus_in'=>'Zoom HDouble- in','zoom_vdouble_plus_out'=>'Zoom VDouble+ out',
      'zoom_hdouble_plus_out'=>'Zoom HDouble+ out','zoom_vdouble_minus_out'=>'Zoom VDouble- out','zoom_hdouble_minus_out'=>'Zoom HDouble- out',
      'zoom_plus_in'=>'Zoom+ in','zoom_plus_in_l'=>'Zoom+ in L','zoom_plus_in_r'=>'Zoom+ in R','zoom_plus_in_t'=>'Zoom+ in T',
      'zoom_plus_in_b'=>'Zoom+ in B','zoom_plus_in_tl'=>'Zoom+ in TL','zoom_plus_in_tr'=>'Zoom+ in TR','zoom_plus_in_bl'=>'Zoom+ in BL',
      'zoom_plus_in_br'=>'Zoom+ in BR','zoom_plus_out'=>'Zoom+ out','zoom_plus_out_l'=>'Zoom+ out L','zoom_plus_out_r'=>'Zoom+ out R',
      'zoom_plus_out_t'=>'Zoom+ out T','zoom_plus_out_b'=>'Zoom+ out B','zoom_plus_out_tl'=>'Zoom+ out TL','zoom_plus_out_tr'=>'Zoom+ out TR',
      'zoom_plus_out_bl'=>'Zoom+ out BL','zoom_plus_out_br'=>'Zoom+ out BR','zoom_minus_in'=>'Zoom- in','zoom_minus_in_l'=>'Zoom- in L',
      'zoom_minus_in_r'=>'Zoom- in R','zoom_minus_in_t'=>'Zoom- in T','zoom_minus_in_b'=>'Zoom- in B','zoom_minus_in_tl'=>'Zoom- in TL',
      'zoom_minus_in_tr'=>'Zoom- in TR','zoom_minus_in_bl'=>'Zoom- in BL','zoom_minus_in_br'=>'Zoom- in BR','zoom_minus_out'=>'Zoom- out',
      'zoom_minus_out_l'=>'Zoom- out L','zoom_minus_out_r'=>'Zoom- out R','zoom_minus_out_t'=>'Zoom- out T','zoom_minus_out_b'=>'Zoom- out B',
      'zoom_minus_out_tl'=>'Zoom- out TL','zoom_minus_out_tr'=>'Zoom- out TR','zoom_minus_out_bl'=>'Zoom- out BL','zoom_minus_out_br'=>'Zoom- out BR',
      'collapse_stairs'=>'Collapse Stairs','collapse_swirl'=>'Collapse Swirl','collapse_square'=>'Collapse Square',
      'collapse_rectangle_cross'=>'Collapse Rectangle Cross','collapse_rectangle'=>'Collapse Rectangle','collapse_cross'=>'Collapse Cross',
      'collapse_circle'=>'Collapse Circle','collapse_zigzag'=>'Collapse ZigZag','collapse_random'=>'Collapse Random',
      'clip_and_chess_in'=>'Clip and Chess in','clip_and_chess_out'=>'Clip and Chess out','clip_and_oblique_chess_in'=>'Clip and Oblique Chess in',
      'clip_and_oblique_chess_out'=>'Clip and Oblique Chess out','clip_and_wave_in'=>'Clip and Wave in','clip_and_wave_out'=>'Clip and Wave out',
      'clip_and_jump_in'=>'Clip and Jump in','clip_and_jump_out'=>'Clip and Jump out','expand_stairs'=>'Expand Stairs',
      'expand_straight'=>'Expand Straight','expand_swirl'=>'Expand Swirl','expand_square'=>'Expand Square',
      'expand_rectangle_cross'=>'Expand Rectangle Cross','expand_rectangle'=>'Expand Rectangle','expand_cross'=>'Expand Cross',
      'expand_zigzag'=>'Expand ZigZag','expand_random'=>'Expand Random','dominoes_stripe'=>'Dominoes Stripe','extrude_out_stripe'=>'Extrude out Stripe',
      'extrude_in_stripe'=>'Extrude in Stripe','horizontal_blind_stripe'=>'Horizontal Blind Stripe','vertical_blind_stripe'=>'Vertical Blind Stripe',
      'horizontal_stripe'=>'Horizontal Stripe','vertical_stripe'=>'Vertical Stripe','horizontal_moving_stripe'=>'Horizontal Moving Stripe',
      'vertical_moving_stripe'=>'Vertical Moving Stripe','horizontal_fade_stripe'=>'Horizontal Fade Stripe',
      'vertical_fade_stripe'=>'Vertical Fade Stripe','horizontal_fly_stripe'=>'Horizontal Fly Stripe','vertical_fly_stripe'=>'Vertical Fly Stripe',
      'horizontal_chess_stripe'=>'Horizontal Chess Stripe','vertical_chess_stripe'=>'Vertical Chess Stripe',
      'horizontal_random_fade_stripe'=>'Horizontal Random Fade Stripe','vertical_random_fade_stripe'=>'Vertical Random Fade Stripe',
      'horizontal_bounce_stripe'=>'Horizontal Bounce Stripe','vertical_bounce_stripe'=>'Vertical Bounce Stripe','wave_out'=>'Wave out',
      'wave_out_eagle'=>'Wave out Eagle','wave_out_swirl'=>'Wave out Swirl','wave_out_zigzag'=>'Wave out ZigZag','wave_out_square'=>'Wave out Square',
      'wave_out_rectangle'=>'Wave out Rectangle','wave_out_circle'=>'Wave out Circle','wave_out_cross'=>'Wave out Cross',
      'wave_out_rectangle_cross'=>'Wave out Rectangle Cross','wave_in'=>'Wave in','wave_in_eagle'=>'Wave in Eagle','wave_in_swirl'=>'Wave in Swirl',
      'wave_in_zigzag'=>'Wave in ZigZag','wave_in_square'=>'Wave in Square','wave_in_rectangle'=>'Wave in Rectangle','wave_in_circle'=>'Wave in Circle',
      'wave_in_cross'=>'Wave in Cross','wave_in_rectangle_cross'=>'Wave in Rectangle Cross','jump_out_straight'=>'Jump out Straight',
      'jump_out_swirl'=>'Jump out Swirl','jump_out_zigzag'=>'Jump out ZigZag','jump_out_square'=>'Jump out Square',
      'jump_out_square_with_chess'=>'Jump out Square with Chess','jump_out_rectangle'=>'Jump out Rectangle','jump_out_circle'=>'Jump out Circle',
      'jump_out_rectangle_cross'=>'Jump out Rectangle Cross','jump_in_straight'=>'Jump in Straight','jump_in_swirl'=>'Jump in Swirl',
      'jump_in_zigzag'=>'Jump in ZigZag','jump_in_square'=>'Jump in Square','jump_in_square_with_chess'=>'Jump in Square with Chess',
      'jump_in_rectangle'=>'Jump in Rectangle','jump_in_circle'=>'Jump in Circle','jump_in_rectangle_cross'=>'Jump in Rectangle Cross',
      'parabola_swirl_in'=>'Parabola Swirl in','parabola_swirl_out'=>'Parabola Swirl out','parabola_zigzag_in'=>'Parabola ZigZag in',
      'parabola_zigzag_out'=>'Parabola ZigZag out','parabola_stairs_in'=>'Parabola Stairs in','parabola_stairs_out'=>'Parabola Stairs out',
      'float_right_random'=>'Float Right Random','float_up_random'=>'Float up Random','float_up_random_with_chess'=>'Float up Random with Chess',
      'float_right_zigzag'=>'Float Right ZigZag','float_up_zigzag'=>'Float up ZigZag','float_up_zigzag_with_chess'=>'Float up ZigZag with Chess',
      'float_right_swirl'=>'Float Right Swirl','float_up_swirl'=>'Float up Swirl','float_up_swirl_with_chess'=>'Float up Swirl with Chess',
      'fly_right_random'=>'Fly Right Random','fly_up_random'=>'Fly up Random','fly_up_random_with_chess'=>'Fly up Random with Chess',
      'fly_right_zigzag'=>'Fly Right ZigZag','fly_up_zigzag'=>'Fly up ZigZag','fly_up_zigzag_with_chess'=>'Fly up ZigZag with Chess',
      'fly_right_swirl'=>'Fly Right Swirl','fly_up_swirl'=>'Fly up Swirl','fly_up_swirl_with_chess'=>'Fly up Swirl with Chess',
      'slide_down'=>'Slide Down','slide_right'=>'Slide Right','bounce_down'=>'Bounce Down','bounce_right'=>'Bounce Right'));

    $GLOBALS['bcorp_shortcodes_data']->bcorp_add_shortcode_var(
      'bcorp_caption_transitions',array(
        ''=>'None','L'=>'L','R'=>'R','T'=>'T','B'=>'B','TL'=>'TL','TR'=>'TR','BL'=>'BL','BR'=>'BR',
        'L|IB'=>'L|IB','R|IB'=>'R|IB','T|IB'=>'T|IB','B|IB'=>'B|IB','TL|IB'=>'TL|IB','TR|IB'=>'TR|IB','BL|IB'=>'BL|IB','BR|IB'=>'BR|IB',
        'L|IE'=>'L|IE','R|IE'=>'R|IE','T|IE'=>'T|IE','B|IE'=>'B|IE','TL|IE'=>'TL|IE','TR|IE'=>'TR|IE','BL|IE'=>'BL|IE','BR|IE'=>'BR|IE',
        'L|EP'=>'L|EP','R|EP'=>'R|EP','T|EP'=>'T|EP','B|EP'=>'B|EP','TL|EP'=>'TL|EP','TR|EP'=>'TR|EP','BL|EP'=>'BL|EP','BR|EP'=>'BR|EP',
        'L*'=>'L*','R*'=>'R*','T*'=>'T*','B*'=>'B*','TL*'=>'TL*','TR*'=>'TR*','BL*'=>'BL*','BR*'=>'BR*',
        'L*IE'=>'L*IE','R*IE'=>'R*IE','T*IE'=>'T*IE','B*IE'=>'B*IE','TL*IE'=>'TL*IE','TR*IE'=>'TR*IE','BL*IE'=>'BL*IE','BR*IE'=>'BR*IE',
        'L*IB'=>'L*IB','R*IB'=>'R*IB','T*IB'=>'T*IB','B*IB'=>'B*IB','TL*IB'=>'TL*IB','TR*IB'=>'TR*IB','BL*IB'=>'BL*IB','BR*IB'=>'BR*IB',
        'L-*IB'=>'L-*IB','R-*IB'=>'R-*IB','T-*IB'=>'T-*IB','B-*IB'=>'B-*IB','TL-*IB'=>'TL-*IB','TR-*IB'=>'TR-*IB','BL-*IB'=>'BL-*IB','BR-*IB'=>'BR-*IB',
        'L*IW'=>'L*IW','R*IW'=>'R*IW','T*IW'=>'T*IW','B*IW'=>'B*IW','TL*IW'=>'TL*IW','TR*IW'=>'TR*IW','BL*IW'=>'BL*IW','BR*IW'=>'BR*IW',
        'L|IE*IE'=>'L|IE*IE','R|IE*IE'=>'R|IE*IE','T|IE*IE'=>'T|IE*IE','B|IE*IE'=>'B|IE*IE','TL|IE*IE'=>'TL|IE*IE','TR|IE*IE'=>'TR|IE*IE',
        'BL|IE*IE'=>'BL|IE*IE','BR|IE*IE'=>'BR|IE*IE',
        'CLIP'=>'CLIP','CLIP|LR'=>'CLIP|LR','CLIP|TB'=>'CLIP|TB','CLIP|L'=>'CLIP|L','CLIP|R'=>'CLIP|R','CLIP|T'=>'CLIP|T','CLIP|B'=>'CLIP|B',
        'MCLIP|L'=>'MCLIP|L','MCLIP|R'=>'MCLIP|R','MCLIP|T'=>'MCLIP|T','MCLIP|B'=>'MCLIP|B',
        'ZM'=>'ZM','ZM|P30'=>'ZM|P30','ZM|P50'=>'ZM|P50','ZM|P70'=>'ZM|P70','ZM|P80'=>'ZM|P80',
        'ZMF|2'=>'ZMF|2','ZMF|3'=>'ZMF|3','ZMF|4'=>'ZMF|4','ZMF|5'=>'ZMF|5','ZMF|10'=>'ZMF|10',
        'ZML|L'=>'ZML|L','ZML|R'=>'ZML|R','ZML|T'=>'ZML|T','ZML|B'=>'ZML|B','ZML|TL'=>'ZML|TL','ZML|TR'=>'ZML|TR','ZML|BL'=>'ZML|BL','ZML|BR'=>'ZML|BR',
        'ZML|IE|L'=>'ZML|IE|L','ZML|IE|R'=>'ZML|IE|R','ZML|IE|T'=>'ZML|IE|T','ZML|IE|B'=>'ZML|IE|B',
        'ZML|IE|TL'=>'ZML|IE|TL','ZML|IE|TR'=>'ZML|IE|TR','ZML|IE|BL'=>'ZML|IE|BL','ZML|IE|BR'=>'ZML|IE|BR',
        'ZMS|L'=>'ZMS|L','ZMS|R'=>'ZMS|R','ZMS|T'=>'ZMS|T','ZMS|B'=>'ZMS|B','ZMS|TL'=>'ZMS|TL','ZMS|TR'=>'ZMS|TR','ZMS|BL'=>'ZMS|BL','ZMS|BR'=>'ZMS|BR',
        'ZM*JDN|LT'=>'ZM*JDN|LT','ZM*JDN|LB'=>'ZM*JDN|LB','ZM*JDN|RT'=>'ZM*JDN|RT','ZM*JDN|RB'=>'ZM*JDN|RB',
        'ZM*JDN|TL'=>'ZM*JDN|TL','ZM*JDN|TR'=>'ZM*JDN|TR','ZM*JDN|BL'=>'ZM*JDN|BL','ZM*JDN|BR'=>'ZM*JDN|BR',
        'ZM*JUP|LT'=>'ZM*JUP|LT','ZM*JUP|LB'=>'ZM*JUP|LB','ZM*JUP|RT'=>'ZM*JUP|RT','ZM*JUP|RB'=>'ZM*JUP|RB',
        'ZM*JUP|TL'=>'ZM*JUP|TL','ZM*JUP|TR'=>'ZM*JUP|TR','ZM*JUP|BL'=>'ZM*JUP|BL','ZM*JUP|BR'=>'ZM*JUP|BR',
        'ZM*JDN|LB*'=>'ZM*JDN|LB*','ZM*JDN|RB*'=>'ZM*JDN|RB*',
        'ZM*JDN1|L'=>'ZM*JDN1|L','ZM*JDN1|R'=>'ZM*JDN1|R','ZM*JDN1|T'=>'ZM*JDN1|T','ZM*JDN1|B'=>'ZM*JDN1|B',
        'ZM*JUP1|L'=>'ZM*JUP1|L','ZM*JUP1|R'=>'ZM*JUP1|R','ZM*JUP1|T'=>'ZM*JUP1|T','ZM*JUP1|B'=>'ZM*JUP1|B',
        'ZM*WVC|LT'=>'ZM*WVC|LT','ZM*WVC|LB'=>'ZM*WVC|LB','ZM*WVC|RT'=>'ZM*WVC|RT','ZM*WVC|RB'=>'ZM*WVC|RB',
        'ZM*WVC|TL'=>'ZM*WVC|TL','ZM*WVC|TR'=>'ZM*WVC|TR','ZM*WVC|BL'=>'ZM*WVC|BL','ZM*WVC|BR'=>'ZM*WVC|BR',
        'ZM*WVR|LT'=>'ZM*WVR|LT','ZM*WVR|LB'=>'ZM*WVR|LB','ZM*WVR|RT'=>'ZM*WVR|RT','ZM*WVR|RB'=>'ZM*WVR|RB',
        'ZM*WVR|TL'=>'ZM*WVR|TL','ZM*WVR|TR'=>'ZM*WVR|TR','ZM*WVR|BL'=>'ZM*WVR|BL','ZM*WVR|BR'=>'ZM*WVR|BR',
        'ZM*WV*J1|LT'=>'ZM*WV*J1|LT','ZM*WV*J1|LB'=>'ZM*WV*J1|LB','ZM*WV*J1|RT'=>'ZM*WV*J1|RT','ZM*WV*J1|RB'=>'ZM*WV*J1|RB',
        'ZM*WV*J1|TL'=>'ZM*WV*J1|TL','ZM*WV*J1|TR'=>'ZM*WV*J1|TR','ZM*WV*J1|BL'=>'ZM*WV*J1|BL','ZM*WV*J1|BR'=>'ZM*WV*J1|BR',
        'ZM*WV*J2|LT'=>'ZM*WV*J2|LT','ZM*WV*J2|LB'=>'ZM*WV*J2|LB','ZM*WV*J2|RT'=>'ZM*WV*J2|RT','ZM*WV*J2|RB'=>'ZM*WV*J2|RB',
        'ZM*WV*J2|TL'=>'ZM*WV*J2|TL','ZM*WV*J2|TR'=>'ZM*WV*J2|TR','ZM*WV*J2|BL'=>'ZM*WV*J2|BL','ZM*WV*J2|BR'=>'ZM*WV*J2|BR',
        'ZM*WV*J3|LT'=>'ZM*WV*J3|LT','ZM*WV*J3|LB'=>'ZM*WV*J3|LB','ZM*WV*J3|RT'=>'ZM*WV*J3|RT','ZM*WV*J3|RB'=>'ZM*WV*J3|RB',
        'ZM*WV*J3|TL'=>'ZM*WV*J3|TL','ZM*WV*J3|TR'=>'ZM*WV*J3|TR','ZM*WV*J3|BL'=>'ZM*WV*J3|BL','ZM*WV*J3|BR'=>'ZM*WV*J3|BR',
        'RTT'=>'RTT','RTT|90'=>'RTT|90','RTT|360'=>'RTT|360',
        'RTT|0'=>'RTT|0','RTT|2'=>'RTT|2','RTT|3'=>'RTT|3','RTT|4'=>'RTT|4','RTT|5'=>'RTT|5','RTT|10'=>'RTT|10',
        'RTTL|L'=>'RTTL|L','RTTL|R'=>'RTTL|R','RTTL|T'=>'RTTL|T','RTTL|B'=>'RTTL|B',
        'RTTL|TL'=>'RTTL|TL','RTTL|TR'=>'RTTL|TR','RTTL|BL'=>'RTTL|BL','RTTL|BR'=>'RTTL|BR',
        'RTTS|L'=>'RTTS|L','RTTS|R'=>'RTTS|R','RTTS|T'=>'RTTS|T','RTTS|B'=>'RTTS|B',
        'RTTS|TL'=>'RTTS|TL','RTTS|TR'=>'RTTS|TR','RTTS|BL'=>'RTTS|BL','RTTS|BR'=>'RTTS|BR',
        'RTT*JDN|L'=>'RTT*JDN|L','RTT*JDN|R'=>'RTT*JDN|R','RTT*JDN|T'=>'RTT*JDN|T','RTT*JDN|B'=>'RTT*JDN|B',
        'RTT*JUP|L'=>'RTT*JUP|L','RTT*JUP|R'=>'RTT*JUP|R','RTT*JUP|T'=>'RTT*JUP|T','RTT*JUP|B'=>'RTT*JUP|B',
        'RTT*JDN|LT'=>'RTT*JDN|LT','RTT*JDN|LB'=>'RTT*JDN|LB','RTT*JDN|RT'=>'RTT*JDN|RT','RTT*JDN|RB'=>'RTT*JDN|RB',
        'RTT*JDN|TL'=>'RTT*JDN|TL','RTT*JDN|TR'=>'RTT*JDN|TR','RTT*JDN|BL'=>'RTT*JDN|BL','RTT*JDN|BR'=>'RTT*JDN|BR',
        'RTT*JUP|LT'=>'RTT*JUP|LT','RTT*JUP|LB'=>'RTT*JUP|LB','RTT*JUP|RT'=>'RTT*JUP|RT','RTT*JUP|RB'=>'RTT*JUP|RB',
        'RTT*JUP|TL'=>'RTT*JUP|TL','RTT*JUP|TR'=>'RTT*JUP|TR','RTT*JUP|BL'=>'RTT*JUP|BL','RTT*JUP|BR'=>'RTT*JUP|BR',
        'RTT*JDN|LB*'=>'RTT*JDN|LB*','RTT*JDN|RB*'=>'RTT*JDN|RB*',
        'RTT*JDN1|L'=>'RTT*JDN1|L','RTT*JDN1|R'=>'RTT*JDN1|R','RTT*JDN1|T'=>'RTT*JDN1|T','RTT*JDN1|B'=>'RTT*JDN1|B',
        'RTT*JUP1|L'=>'RTT*JUP1|L','RTT*JUP1|R'=>'RTT*JUP1|R','RTT*JUP1|T'=>'RTT*JUP1|T','RTT*JUP1|B'=>'RTT*JUP1|B',
        'RTT*JDN1|TL'=>'RTT*JDN1|TL','RTT*JDN1|TR'=>'RTT*JDN1|TR','RTT*JDN1|BL'=>'RTT*JDN1|BL',
        'RTT*JUP1|TL'=>'RTT*JUP1|TL','RTT*JUP1|TR'=>'RTT*JUP1|TR','RTT*JUP1|BL'=>'RTT*JUP1|BL',
        'RTT*WVC|LT'=>'RTT*WVC|LT','RTT*WVC|LB'=>'RTT*WVC|LB','RTT*WVC|RT'=>'RTT*WVC|RT','RTT*WVC|RB'=>'RTT*WVC|RB',
        'RTT*WVC|TL'=>'RTT*WVC|TL','RTT*WVC|TR'=>'RTT*WVC|TR','RTT*WVC|BL'=>'RTT*WVC|BL','RTT*WVC|BR'=>'RTT*WVC|BR',
        'RTT*WVR|LT'=>'RTT*WVR|LT','RTT*WVR|LB'=>'RTT*WVR|LB','RTT*WVR|RT'=>'RTT*WVR|RT','RTT*WVR|RB'=>'RTT*WVR|RB',
        'RTT*WVR|TL'=>'RTT*WVR|TL','RTT*WVR|TR'=>'RTT*WVR|TR','RTT*WVR|BL'=>'RTT*WVR|BL','RTT*WVR|BR'=>'RTT*WVR|BR',
        'RTT*WV*J1|LT'=>'RTT*WV*J1|LT','RTT*WV*J1|LB'=>'RTT*WV*J1|LB','RTT*WV*J1|RT'=>'RTT*WV*J1|RT','RTT*WV*J1|RB'=>'RTT*WV*J1|RB',
        'RTT*WV*J1|TL'=>'RTT*WV*J1|TL','RTT*WV*J1|TR'=>'RTT*WV*J1|TR','RTT*WV*J1|BL'=>'RTT*WV*J1|BL','RTT*WV*J1|BR'=>'RTT*WV*J1|BR',
        'RTT*WV*J2|LT'=>'RTT*WV*J2|LT','RTT*WV*J2|LB'=>'RTT*WV*J2|LB','RTT*WV*J2|RT'=>'RTT*WV*J2|RT','RTT*WV*J2|RB'=>'RTT*WV*J2|RB',
        'RTT*WV*J2|TL'=>'RTT*WV*J2|TL','RTT*WV*J2|TR'=>'RTT*WV*J2|TR','RTT*WV*J2|BL'=>'RTT*WV*J2|BL','RTT*WV*J2|BR'=>'RTT*WV*J2|BR',
        'RTT*WV*J3|LT'=>'RTT*WV*J3|LT','RTT*WV*J3|LB'=>'RTT*WV*J3|LB','RTT*WV*J3|RT'=>'RTT*WV*J3|RT','RTT*WV*J3|RB'=>'RTT*WV*J3|RB',
        'RTT*WV*J3|TL'=>'RTT*WV*J3|TL','RTT*WV*J3|TR'=>'RTT*WV*J3|TR','RTT*WV*J3|BL'=>'RTT*WV*J3|BL','RTT*WV*J3|BR'=>'RTT*WV*J3|BR',
        'DDG|TL'=>'DDG|TL','DDG|TR'=>'DDG|TR','DDG|BL'=>'DDG|BL','DDG|BR'=>'DDG|BR',
        'DDGDANCE|LT'=>'DDGDANCE|LT','DDGDANCE|RT'=>'DDGDANCE|RT','DDGDANCE|LB'=>'DDGDANCE|LB','DDGDANCE|RB'=>'DDGDANCE|RB',
        'DDGPET|LT'=>'DDGPET|LT','DDGPET|LB'=>'DDGPET|LB','DDGPET|RT'=>'DDGPET|RT','DDGPET|RB'=>'DDGPET|RB',
        'FLTTR|L'=>'FLTTR|L','FLTTR|R'=>'FLTTR|R','FLTTR|T'=>'FLTTR|T','FLTTR|B'=>'FLTTR|B',
        'FLTTRWN|LT'=>'FLTTRWN|LT','FLTTRWN|LB'=>'FLTTRWN|LB','FLTTRWN|RT'=>'FLTTRWN|RT','FLTTRWN|RB'=>'FLTTRWN|RB',
        'FLTTRWN|TL'=>'FLTTRWN|TL','FLTTRWN|TR'=>'FLTTRWN|TR','FLTTRWN|BL'=>'FLTTRWN|BL','FLTTRWN|BR'=>'FLTTRWN|BR',
        'LATENCY|LT'=>'LATENCY|LT','LATENCY|LB'=>'LATENCY|LB','LATENCY|RT'=>'LATENCY|RT','LATENCY|RB'=>'LATENCY|RB',
        'LATENCY|TL'=>'LATENCY|TL','LATENCY|TR'=>'LATENCY|TR','LATENCY|BL'=>'LATENCY|BL','LATENCY|BR'=>'LATENCY|BR',
        'TORTUOUS|HL'=>'TORTUOUS|HL','TORTUOUS|HR'=>'TORTUOUS|HR','TORTUOUS|VB'=>'TORTUOUS|VB','TORTUOUS|VT'=>'TORTUOUS|VT',
        'TORTUOUS|LT'=>'TORTUOUS|LT','TORTUOUS|LB'=>'TORTUOUS|LB','TORTUOUS|RT'=>'TORTUOUS|RT','TORTUOUS|RB'=>'TORTUOUS|RB',
        'TORTUOUS|TL'=>'TORTUOUS|TL','TORTUOUS|TR'=>'TORTUOUS|TR','TORTUOUS|BL'=>'TORTUOUS|BL','TORTUOUS|BR'=>'TORTUOUS|BR',
        'SPACESHIP|LT'=>'SPACESHIP|LT','SPACESHIP|LB'=>'SPACESHIP|LB','SPACESHIP|RT'=>'SPACESHIP|RT','SPACESHIP|RB'=>'SPACESHIP|RB',
        'ATTACK|LT'=>'ATTACK|LT','ATTACK|LB'=>'ATTACK|LB','ATTACK|RT'=>'ATTACK|RT','ATTACK|RB'=>'ATTACK|RB',
        'ATTACK|TL'=>'ATTACK|TL','ATTACK|TR'=>'ATTACK|TR','ATTACK|BL'=>'ATTACK|BL','ATTACK|BR'=>'ATTACK|BR',
        'LISTV|L'=>'LISTV|L','LISTV|R'=>'LISTV|R','LISTH|L'=>'LISTH|L','LISTH|R'=>'LISTH|R',
        'LISTVC|L'=>'LISTVC|L','LISTVC|R'=>'LISTVC|R','LISTVC|B'=>'LISTVC|B','LISTVC|T'=>'LISTVC|T',
        'LISTHC|L'=>'LISTHC|L','LISTHC|R'=>'LISTHC|R','LISTHC|B'=>'LISTHC|B','LISTHC|T'=>'LISTHC|T',
        'WV|L'=>'WV|L','WV|R'=>'WV|R','WV|T'=>'WV|T','WV|B'=>'WV|B','WVC|L'=>'WVC|L','WVC|R'=>'WVC|R','WVC|T'=>'WVC|T','WVC|B'=>'WVC|B',
        'WVR|L'=>'WVR|L','WVR|R'=>'WVR|R','JDN|L'=>'JDN|L','JDN|R'=>'JDN|R','JDN|T'=>'JDN|T','JDN|B'=>'JDN|B',
        'JUP|L'=>'JUP|L','JUP|R'=>'JUP|R','JUP|T'=>'JUP|T','JUP|B'=>'JUP|B','FADE'=>'FADE',
        'FADE*JDN|L'=>'FADE*JDN|L','FADE*JDN|R'=>'FADE*JDN|R','FADE*JDN|T'=>'FADE*JDN|T','FADE*JDN|B'=>'FADE*JDN|B',
        'FADE*JUP|L'=>'FADE*JUP|L','FADE*JUP|R'=>'FADE*JUP|R','FADE*JUP|T'=>'FADE*JUP|T','FADE*JUP|B'=>'FADE*JUP|B',
        'L-JDN'=>'L-JDN','R-JDN'=>'R-JDN','T-JDN'=>'T-JDN','B-JDN'=>'B-JDN','L-JUP'=>'L-JUP','R-JUP'=>'R-JUP','T-JUP'=>'T-JUP','B-JUP'=>'B-JUP',
        'L-WVC'=>'L-WVC','R-WVC'=>'R-WVC','T-WVC'=>'T-WVC','B-WVC'=>'B-WVC','L-WVR'=>'L-WVR','R-WVR'=>'R-WVR','T-WVR'=>'T-WVR','B-WVR'=>'B-WVR',
        'CLIP-FADE'=>'CLIP-FADE','CLIP|LR-FADE'=>'CLIP|LR-FADE','CLIP|TB-FADE'=>'CLIP|TB-FADE',
        'CLIP|L-FADE'=>'CLIP|L-FADE','CLIP|R-FADE'=>'CLIP|R-FADE','CLIP|T-FADE'=>'CLIP|T-FADE','CLIP|B-FADE'=>'CLIP|B-FADE',
        'MCLIP|L-FADE'=>'MCLIP|L-FADE','MCLIP|R-FADE'=>'MCLIP|R-FADE','MCLIP|T-FADE'=>'MCLIP|T-FADE','MCLIP|B-FADE'=>'MCLIP|B-FADE',
        'L*CLIP'=>'L*CLIP','R*CLIP'=>'R*CLIP','T*CLIP'=>'T*CLIP','B*CLIP'=>'B*CLIP',
        'T-L*'=>'T-L*','T-R*'=>'T-R*','B-L*'=>'B-L*','B-R*'=>'B-R*','L-T*'=>'L-T*','L-B*'=>'L-B*','R-T*'=>'R-T*','R-B*'=>'R-B*',
        'FADE-L*'=>'FADE-L*','FADE-R*'=>'FADE-R*','FADE-T*'=>'FADE-T*','FADE-B*'=>'FADE-B*'));

  global $bcorp_full_width_theme;
  if ($bcorp_full_width_theme) $GLOBALS['bcorp_shortcodes_data']->bcorp_add_shortcode(
    "bcorp_blog_slider",array(
      "title"=>"Blog Slider",
      "admin_icon"=>"&#xe81E;",
      "closing_tag"=>false,
      "admin_default"=>'<div class="bcve-bcorp_blog_slider"><i class="bcve-icon bcve-header-icon">&#xe81E;</i><div class="bcve-bcorp_blog-details">Filtered by: <span class="bcve-bcorp_blog_slider-filterby">category</span><br />
  Fullwidth: <span class="bcve-bcorp_blog_slider-fullwidth">false</span><br />
  Posts Per Page: <span class="bcve-bcorp_blog_slider-count">12</span></div></div>',
      "variables"=>array(
        'fullwidth'=>array(
          'name'=>'Enable Full Page Width',
          'description'=>'Requires a BCorp or compatible theme for full width.  Unpredictable results may occur with incompatible themes.',
          'type'=>'checkbox',
          'default'=>'false'),
        'style'=>array(
          'name'=>'Style',
          'type'=>'dropdown',
          'default'=>'slider',
          'dependents'=>array(
            'carousel'=>array('columns')),
          'values'=>array(
            'slider'=>'Standard Slider',
            'carousel'=>'Carousel')),
        'columns'=>array(
          'name'=>'Columns',
          'type'=>'dropdown',
          'default'=>'2',
          'values'=>array(
            '1'=>'1 Column',
            '2'=>'2 Columns',
            '3'=>'3 Columns')),
        'filterby'=>array(
          'name'=>'Filter Posts By',
          'type'=>'dropdown',
          'dependents'=>array(
            'category'=>array('categories'),
            'tag'=>array('tags'),
            'formats'=>array('formats'),
            'portfolios'=>array('portfolios')),
          'default'=>'category',
          'values'=>array(
            'category'=>'Category',
            'tag'=>'Tags',
            'formats'=>'Post Format',
            'portfolios'=>'Portfolio Entries')),
        'categories'=>array(
          'name'=>'Categories',
          'type'=>'dropdown',
          'default'=>'',
          'selectmultiple'=>true,
          'values'=>'categories'),
        'tags'=>array(
          'name'=>'Tags',
          'type'=>'dropdown',
          'default'=>'',
          'selectmultiple'=>true,
          'values'=>'tags'),
        'formats'=>array(
          'name'=>'Post Formats',
          'type'=>'dropdown',
          'default'=>'',
          'selectmultiple'=>true,
          'values'=>'post_formats'),
        'portfolios'=>array(
          'name'=>'Portfolio Categories',
          'type'=>'dropdown',
          'default'=>'',
          'selectmultiple'=>true,
          'values'=>'portfolios'),
        'size'=>array(
          'name'=>'Image Size',
          'type'=>'dropdown',
          'dependents'=>array(
            'custom'=>array('customsize')),
          'default'=>'automatic',
          'values'=>array(
            'automatic'=>'Use Default Image Size',
            'custom'=>'Choose Custom Image Size')),
        'minheight'=>array(
          'name'=>'Minimum Height',
          'default'=>'0',
          'type'=>'textfield',
          'description'=>'Minimum Height in Pixels'),
        'customsize'=>array(
          'name'=>'Custom Image Size',
          'type'=>'dropdown',
          'default'=>'medium',
          'values'=>'bcorp_image_sizes'),
        'count'=>array(
          'name'=>'Posts per Page',
          'type'=>'dropdown',
          'default'=>'12',
          'values'=>'1to100all'),
        'offset'=>array(
          'name'=>'Offset',
          'type'=>'dropdown',
          'default'=>'0',
          'values'=>'0to100'),

      )
    )
      ,'bcorp_slider');

      if (!$bcorp_full_width_theme) $GLOBALS['bcorp_shortcodes_data']->bcorp_add_shortcode(
        "bcorp_blog_slider",array(
          "title"=>"Blog Slider",
          "admin_icon"=>"&#xe81E;",
          "closing_tag"=>false,
          "admin_default"=>'<div class="bcve-bcorp_blog_slider"><i class="bcve-icon bcve-header-icon">&#xe81E;</i><div class="bcve-bcorp_blog-details">Filtered by: <span class="bcve-bcorp_blog_slider-filterby">category</span><br />
      Fullwidth: <span class="bcve-bcorp_blog_slider-fullwidth">false</span><br />
      Posts Per Page: <span class="bcve-bcorp_blog_slider-count">12</span></div></div>',
          "variables"=>array(
            'style'=>array(
              'name'=>'Style',
              'type'=>'dropdown',
              'default'=>'slider',
              'values'=>array(
                'slider'=>'Standard Slider')),
            'filterby'=>array(
              'name'=>'Filter Posts By',
              'type'=>'dropdown',
              'dependents'=>array(
                'category'=>array('categories'),
                'tag'=>array('tags'),
                'formats'=>array('formats'),
                'portfolios'=>array('portfolios')),
              'default'=>'category',
              'values'=>array(
                'category'=>'Category',
                'tag'=>'Tags',
                'formats'=>'Post Format',
                'portfolios'=>'Portfolio Entries')),
            'categories'=>array(
              'name'=>'Categories',
              'type'=>'dropdown',
              'default'=>'',
              'selectmultiple'=>true,
              'values'=>'categories'),
            'tags'=>array(
              'name'=>'Tags',
              'type'=>'dropdown',
              'default'=>'',
              'selectmultiple'=>true,
              'values'=>'tags'),
            'formats'=>array(
              'name'=>'Post Formats',
              'type'=>'dropdown',
              'default'=>'',
              'selectmultiple'=>true,
              'values'=>'post_formats'),
            'portfolios'=>array(
              'name'=>'Portfolio Categories',
              'type'=>'dropdown',
              'default'=>'',
              'selectmultiple'=>true,
              'values'=>'portfolios'),
            'size'=>array(
              'name'=>'Image Size',
              'type'=>'dropdown',
              'dependents'=>array(
                'custom'=>array('customsize')),
              'default'=>'automatic',
              'values'=>array(
                'automatic'=>'Use Default Image Size',
                'custom'=>'Choose Custom Image Size')),
            'minheight'=>array(
              'name'=>'Minimum Height',
              'default'=>'0',
              'type'=>'textfield',
              'description'=>'Minimum Height in Pixels'),
            'customsize'=>array(
              'name'=>'Custom Image Size',
              'type'=>'dropdown',
              'default'=>'medium',
              'values'=>'bcorp_image_sizes'),
            'count'=>array(
              'name'=>'Posts per Page',
              'type'=>'dropdown',
              'default'=>'12',
              'values'=>'1to100all'),
            'offset'=>array(
              'name'=>'Offset',
              'type'=>'dropdown',
              'default'=>'0',
              'values'=>'0to100'),

          )
        )
          ,'bcorp_slider');

$GLOBALS['bcorp_shortcodes_data']->bcorp_add_shortcode(
  "bcorp_slider",array(
    "type"=>"section",
    "admin_icon"=>"&#xe81D;",
    "title"=>"Slider",
    "accept_content"=>true,
    "child_element"=>"bcorp_slide",
    "width" => "1-1",
    "admin_default"=>'<div class="bcve-bcorp_slider"><i class="bcve-icon bcve-header-icon">&#xe81D;</i><div class="bcve-bcorp_slider-details">Size: <span class="bcve-bcorp_slider-size">standard</span><br />
  Transitions: <span class="bcve-bcorp_slider-transitions">fade</span><br />
  Slide Size: <span class="bcve-bcorp_slider-slidesize">large</span></div></div>',
    "variables"=>array(
      'size'=>array(
        'name'=>'Slider Size',
        'description'=>'Requires a BCorp or compatible theme for full width. Unpredictable results may occur with incompatible themes.',
        'type'=>'dropdown',
        'default'=>'standard',
        'values'=>array(
          'standard'=>'Standard Slider',
          'fullwidth'=>'Full Screen Width',
          'fullscreen'=>'Full Screen Width & Height',
        )
      ),
      'transitions'=>array(
        'name'=>'Transitions',
        'type'=>'dropdown',
        'default'=>'fade',
        'selectmultiple'=>true,
        'values'=>'bcorp_slider_transitions'),
      'slidesize'=>array(
        'name'=>'Slider Image Size',
        'type'=>'dropdown',
        'default'=>'large',
        'values'=>'bcorp_image_sizes'
      ),
      'autoplay'=>array(
        'name'=>'Automatically Advance Slides',
        'type'=>'checkbox',
        'dependents'=>array(
          'true'=>array('speed')),
        'default' =>'true'),
      'speed'=>array(
        'name'=>'Transition Speed',
        'description'=>'Enter transition speed in milliseconds',
        'type'=>'textfield',
        'default' =>'7000'),
      'minheight'=>array(
        'name'=>'Minimum Height',
        'default'=>'0',
        'type'=>'textfield',
        'description'=>'Minimum Height in Pixels'),
    )
  )
    ,'bcorp_slider');

$GLOBALS['bcorp_shortcodes_data']->bcorp_add_shortcode(
  "bcorp_slide",array(
    "title"=>"Slide",
    "admin_icon"=>"&#xe81D;",
    "only_child"=>true,
    "accept_content"=>true,
    "child_element"=>"bcorp_slide_cell",
    "parent_element"=>"bcorp_slider",
    "width" => "1-1",
    "admin_default" => '<div class="bcve-bcorp_slide"><div class="bcve-bcorp_slide-id"><div class="bcve-image-placeholder">&#xe804;</div></div>
      <div class="bcve-bcorp_slide-details">Video: <span class="bcve-bcorp_slide-location">none</span><br /></div></div>',
    "variables"=>array(
      'id'=>array(
        'name'=>'Image ID',
        'type'=>'image',
        'default' =>''),
      'location'=>array(
        'name'=>'Video Location',
        'type'=>'dropdown',
        'dependents'=>array(
          'youtube'=>array('video','controls','mute','autoplay','loop'),
          'vimeo'=>array('video','controls','mute','autoplay','loop')),
        'default'=>'none',
          'values'=>array(
            'none'=>'No Background Video',
            'youtube'=>'You Tube',
            'vimeo'=>'Vimeo')),
      'video'=>array(
        'name'=>'Video ID',
        'type'=>'textfield',
        'default' =>''),
      'controls'=>array(
        'name'=>'Video Controls',
        'type'=>'checkbox',
        'default'=>'true'),
      'mute'=>array(
        'name'=>'Mute Video',
        'type'=>'checkbox',
        'default'=>'true'),
      'autoplay'=>array(
        'name'=>'Autoplay Video',
        'type'=>'checkbox',
        'default'=>'true'),
      'loop'=>array(
        'name'=>'Loop Video',
        'type'=>'checkbox',
        'default'=>'true'),
    )
  )
    ,'bcorp_slider');

$GLOBALS['bcorp_shortcodes_data']->bcorp_add_shortcode(
"bcorp_slide_cell",array(
  "title"=>"Slide Cell",
  "admin_icon"=>"&#xe81D;",
  "only_child"=>true,
  "width"=>"1-4-min",
  "parent_element"=>"bcorp_slide",
  "admin_default" => '<div class="bcve-bcorp_slide_cell"><div class="bcve-bcorp_slide_cell-id"><div class="bcve-image-placeholder">&#xe804;</div></div>
    <span class="bcve-bcorp_slide_cell-textblock"></span></div>',
  "variables"=>array(
    'id'=>array(
      'name'=>'Image ID',
      'type'=>'image',
      'default' =>''),
    'icon'=>array(
      'name'=>'Icon',
      'type'=>'icon',
      'default'=>''),
    'button'=>array(
      'name'=>'Wrap in Button',
      'admin_tab'=>'Button',
      'type'=>'checkbox',
      'dependents'=>array(
        'true'=>array('link','icon','color','size')),
      'default'=>'false'),
    'link'=>'link',
    'color'=>array(
      'name'=>'Button Color',
      'type'=>'color',
      'default'=>''),
    'size'=>array(
      'name'=>'Size',
      'type'=>'dropdown',
      'default'=>'medium',
      'values'=>array(
        'small'=>'Small',
        'medium'=>'Medium',
        'large'=>'Large')),
    'textcolor'=>array(
      'name'=>'Text Color',
      'type'=>'color',
      'default'=>''),
    'fontsize'=>array(
      'name'=>'Font Size',
      'description'=>'Enter a font size in pixels.',
      'type'=>'textfield',
      'default' =>'20'),
    'lineheight'=>array(
      'name'=>'Line Height',
      'description'=>'Enter a line height in pixels.',
      'type'=>'textfield',
      'default' =>'30'),
    'align'=>array(
      'name'=>'Alignment',
      'type'=>'dropdown',
      'default'=>'center',
      'values'=>array(
        'left'=>'Left',
        'center'=>'Center',
        'right'=>'Right')),
    'boxcaption'=>array(
      'name'=>'Opaque Background',
      'admin_tab'=>'Background',
      'type'=>'checkbox',
      'dependents'=>array(
        'true'=>array('backgroundcolor','opacity','radius','paddingtop','paddingright','paddingbottom','paddingleft')),
      'default'=>'false'),
    'backgroundcolor'=>array(
      'name'=>'Background Color',
      'type'=>'color',
      'default'=>''),
    'opacity'=>array(
      'name'=>'Opacity',
      'description'=>'From 0 invisible to 1 solid',
      'type'=>'textfield',
      'default'=>'0.5'),
    'radius'=>array(
      'name'=>'Radius',
      'description'=>'Border Radius in Pixels',
      'type'=>'textfield',
      'default'=>'4'),
    'paddingtop'=>array(
      'name'=>'Padding Top',
      'description'=>'Padding Top in Pixels',
      'type'=>'textfield',
      'default'=>'0'),
    'paddingright'=>array(
      'name'=>'Padding Right',
      'description'=>'Padding Right in Pixels',
      'type'=>'textfield',
      'default'=>'0'),
    'paddingbottom'=>array(
      'name'=>'Padding Bottom',
      'description'=>'Padding Bottom in Pixels',
      'type'=>'textfield',
      'default'=>'0'),
    'paddingleft'=>array(
      'name'=>'Padding Left',
      'description'=>'Padding Left in Pixels',
      'type'=>'textfield',
      'default'=>'0'),
    'left'=>array(
      'name'=>'Left',
      'admin_tab'=>'Layout',
      'description'=>'X Coordinate in pixels',
      'type'=>'textfield',
      'default'=>'30'),
    'top'=>array(
      'name'=>'Top',
      'admin_tab'=>'Layout',
      'description'=>'Y Coordinate in pixels',
      'type'=>'textfield',
      'default'=>'30'),
    'width'=>array(
      'name'=>'Width',
      'admin_tab'=>'Layout',
      'description'=>'Enter a value in pixels',
      'type'=>'textfield',
      'default'=>'300'),
    'height'=>array(
      'name'=>'height',
      'admin_tab'=>'Layout',
      'description'=>'Enter a value in pixels',
      'type'=>'textfield',
      'default'=>'30'),
    't'=>array(
      'name'=>'Play in Transition',
      'admin_tab'=>'Animation',
      'type'=>'dropdown',
      'default'=>'',
      'values'=>'bcorp_caption_transitions'),
    't2'=>array(
      'name'=>'Play out Transition',
      'admin_tab'=>'Animation',
      'type'=>'dropdown',
      'default'=>'',
      'values'=>'bcorp_caption_transitions'),
    'd'=>array(
      'name'=>'Caption Delay',
      'admin_tab'=>'Animation',
      'description'=>'Optional delay in milliseconds to play this caption since the previous caption stopped',
      'type'=>'textfield',
      'default'=>''),
    'du'=>array(
      'name'=>'Play In Duration',
      'admin_tab'=>'Animation',
      'description'=>'Optional explicitly set duration in milliseconds to play in',
      'type'=>'textfield',
      'default'=>''),
    'du2'=>array(
      'name'=>'Play Out Duration',
      'admin_tab'=>'Animation',
      'description'=>'Optional explicitly set duration in milliseconds to play out',
      'type'=>'textfield',
      'default'=>''),
    'textblock'=>array(
      'name'=>'Caption',
      'type'=>'textarea',
      'editor'=>'tinymce',
      'default' =>''))
  )
    ,'bcorp_slider');

  }
}
?>
