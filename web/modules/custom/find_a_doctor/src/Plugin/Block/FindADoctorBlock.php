<?php

namespace Drupal\find_a_doctor\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides 'Find a Doctor' Block.
 */

#[Block(
  id: "find_a_doctor_block",
  admin_label: new TranslatableMarkup("Find a Doctor block"),
  category: new TranslatableMarkup("Custom Block")
)]

class FindADoctorBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Custom React Jobs Teaser Block.

    // METHOD A - this does not work as of May 2025 with D11
    // $build = [ // This method does not work and throws a "invalid render array key. Value should be an array but got a string" error (no matter how the data is structured), possibly due to a Drupal core bug, as documented here: https://www.drupal.org/project/drupal/issues/3470907
    //   '#theme' => 'find_a_doctor_display',
    //   '#data' => [
    //     'title' => 'Find a Doctor',
    //     'description' => 'Search for a doctor by name, specialty, or location.'
    //   ],
    //   '#attached' => [
    //     'library' => 'find_a_doctor/find_a_doctor_lib'
    //   ],
    // ];

    // METHOD B - this works as of May 2025 with D11
    $initial_state = [
      'title' => 'Find a Doctor',
      'description' => 'Search for a doctor by name, specialty, or location.',
    ];
    $build = [
      '#markup' => "<div id='find-a-doctor-root' data-initial-state='".json_encode($initial_state)."'></div>", // Note: use double-tick -> single-tick (instead of single-tick -> double-tick), so the double-ticks in the JSON string don't break the HTML string.
      '#attached' => [
        'library' => 'find_a_doctor/find_a_doctor_lib'
      ],
    ];
    return $build;
  }

}

