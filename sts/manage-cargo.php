<?php
$cargo_file = 'cargo_list.txt';

function get_cargo() {
    global $cargo_file;
    return file($cargo_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

function remove_cargo($cargo) {
    global $cargo_file;
    $cargo_list = get_cargo();
    $cargo_list = array_diff($cargo_list, [$cargo]);
    file_put_contents($cargo_file, implode("\n", $cargo_list));
}

function reset_cargo() {
    global $cargo_file;
    $original_cargo = [
        'Coal', 'Grain', 'Timber', 'Oil', 'Ore', 
        'Containers', 'Automobiles', 'Livestock'
    ];
    //file_put_contents($cargo_file, implode("\n", $original_cargo));
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        echo json_encode(get_cargo());
        break;
    case 'remove':
        $cargo = $_GET['cargo'] ?? '';
        if ($cargo) {
            remove_cargo($cargo);
        }
        echo json_encode(['success' => true]);
        break;
    case 'reset':
        reset_cargo();
        echo json_encode(['success' => true]);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
