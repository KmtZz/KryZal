<?php
class recompensaDAO
{
    private PDO $con;
    public function __construct(PDO $con)
    {
        $this->con = $con;
    }
    public function canjear(int $usuario, int $premio, string $peticion): void
    {
        $catalogo = reward_catalog();
        if (!isset($catalogo[$premio]) || !preg_match('/^[a-f0-9]{64}$/', $peticion)) {
            throw new RuntimeException('Recompensa no válida.');
        }
        try {
            $this->con->beginTransaction();
            $stmt = $this->con->prepare(
                'SELECT points FROM users WHERE id=? AND active=1 FOR UPDATE',
            );
            $stmt->execute([$usuario]);
            $puntos = $stmt->fetchColumn();
            $stmt = $this->con->prepare(
                'SELECT id FROM redemptions WHERE request_key=? AND user_id=?',
            );
            $stmt->execute([$peticion, $usuario]);
            if ($stmt->fetch()) {
                $this->con->commit();
                return;
            }
            [$titulo, $coste] = $catalogo[$premio];
            if ($puntos === false || (int) $puntos < $coste) {
                throw new RuntimeException('No tienes puntos suficientes.');
            }
            $codigo = strtoupper(bin2hex(random_bytes(12)));
            $caducidad = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
            $stmt = $this->con->prepare(
                'INSERT INTO redemptions(user_id,reward_id,title,cost,code,request_key,expires_at) VALUES(?,?,?,?,?,?,?)',
            );
            $stmt->execute([$usuario, $premio, $titulo, $coste, $codigo, $peticion, $caducidad]);
            $stmt = $this->con->prepare('UPDATE users SET points=points-? WHERE id=?');
            $stmt->execute([$coste, $usuario]);
            $this->con->commit();
        } catch (Throwable $e) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }
            throw $e;
        }
    }
    public function utilizar(string $codigo, int $admin): void
    {
        $stmt = $this->con->prepare(
            'UPDATE redemptions SET used_at=NOW(),used_by=? WHERE code=? AND used_at IS NULL AND expires_at>NOW()',
        );
        $stmt->execute([$admin, $codigo]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Código inexistente, caducado o ya utilizado.');
        }
    }
}
