<?php
class puntosDAO
{
    private PDO $con;

    public function __construct(PDO $con)
    {
        $this->con = $con;
    }

    public function modificar(
        int $usuario,
        int $admin,
        int $saldo,
        int $puntuacion,
        int $saldoAnterior,
        int $puntuacionAnterior,
    ): void {
        try {
            $this->con->beginTransaction();
            $stmt = $this->con->prepare(
                "SELECT id,points FROM users WHERE id=? AND role='usuario' FOR UPDATE",
            );
            $stmt->execute([$usuario]);
            $datos = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$datos) {
                throw new RuntimeException('No se ha encontrado el socio.');
            }

            $stmt = $this->con->prepare(
                'SELECT points+COALESCE((SELECT SUM(cost) FROM redemptions WHERE user_id=users.id),0)+COALESCE((SELECT SUM(score_delta-balance_delta) FROM point_adjustments WHERE user_id=users.id),0) FROM users WHERE id=?',
            );
            $stmt->execute([$usuario]);
            $actual = (int) $stmt->fetchColumn();
            if ((int) $datos['points'] !== $saldoAnterior || $actual !== $puntuacionAnterior) {
                throw new RuntimeException(
                    'Los puntos han cambiado desde que abriste la página. Recarga y revisa los valores antes de guardar.',
                );
            }

            $cambioSaldo = $saldo - (int) $datos['points'];
            $cambioPuntuacion = $puntuacion - $actual;
            if ($cambioSaldo !== 0 || $cambioPuntuacion !== 0) {
                $stmt = $this->con->prepare(
                    'INSERT INTO point_adjustments(user_id,admin_id,balance_delta,score_delta) VALUES(?,?,?,?)',
                );
                $stmt->execute([$usuario, $admin, $cambioSaldo, $cambioPuntuacion]);
                $stmt = $this->con->prepare('UPDATE users SET points=? WHERE id=?');
                $stmt->execute([$saldo, $usuario]);
            }
            $this->con->commit();
        } catch (Throwable $e) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }
            throw $e;
        }
    }
}
