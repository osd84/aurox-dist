<?php


use App\AppUrls;
use OsdAurox\FormValidator;
use OsdAurox\Base;
use OsdAurox\Forms;
use OsdAurox\Modal;
use OsdAurox\Sec;
use OsdAurox\Validator;

$title = 'Aurox';

require_once '../aurox.php';

header('Content-Type: application/json');
$response = [
    'results' => [
        [
            'id' => Sec::hNoHtml(1),
            'text' => Sec::hNoHtml('CAT 1')
        ],
        [
            'id' => Sec::hNoHtml(2),
            'text' => Sec::hNoHtml('CAT 2')
        ],
        [
            'id' => Sec::hNoHtml(3),
            'text' => Sec::hNoHtml('CAT 3')
        ],
        [
            'id' => '*',
            'text' => Sec::hNoHtml('Invalid CAT')
        ]
    ],
    'pagination' => ['more' => false]
];
echo json_encode($response);