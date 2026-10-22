<?php

switch ($action) {
    case 'redeem':
        require_role(['usuario']);
        require_once __DIR__ . '/../../Modelo/recompensaDAO.php';
        $rewards = new recompensaDAO($pdo);
        $id = (int) ($_POST['reward_id'] ?? 0);
        $key = $_POST['request_key'] ?? '';
        if (!isset($_SESSION['reward_tokens'][$key])) {
            throw new RuntimeException('Recarga el catálogo antes de canjear.');
        }
        $rewards->canjear((int) $me['id'], $id, $key);
        flash('Recompensa canjeada. Tu código está en Mis recompensas.');
        go('myrewards');
    case 'use_reward':
        require_role(['administrador']);
        require_once __DIR__ . '/../../Modelo/recompensaDAO.php';
        $rewards = new recompensaDAO($pdo);
        $rewards->utilizar(strtoupper(trim($_POST['code'] ?? '')), (int) $me['id']);
        flash('Código validado y marcado como utilizado.');
        go('rewardcheck&code=' . urlencode(strtoupper(trim($_POST['code'] ?? ''))));
}
