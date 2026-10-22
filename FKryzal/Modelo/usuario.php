<?php

class usuario //Objeto usuario con sus setter y getters
{
    private $id;
    private $nombre;
    private $email;
    private $contraseña;
    private $rol;
    private $puntos;
    private $estado;
    private $nuevo;
    public function __construct($id, $nombre, $email, $contraseña, $rol, $puntos, $estado, $nuevo)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->contraseña = $contraseña;
        $this->rol = $rol;
        $this->puntos = $puntos;
        $this->estado = $estado;
        $this->nuevo = $nuevo;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getNombre()
    {
        return h($this->nombre);
    }
    public function getEmail()
    {
        return h($this->email);
    }
    public function getContraseña()
    {
        return $this->contraseña;
    }
    public function getRol()
    {
        return $this->rol;
    }
    public function getPuntos()
    {
        return $this->puntos;
    }
    public function getEstado()
    {
        return $this->estado;
    }
    public function getNuevo()
    {
        return $this->nuevo;
    }
    public function setId($id)
    {
        $this->id = $id;
    }
    public function setNombre($nombre)
    {
        $this->nombre = $nombre;
    }
    public function setEmail($email)
    {
        $this->email = $email;
    }
    public function setContraseña($contraseña)
    {
        $this->contraseña = $contraseña;
    }
    public function setRol($rol)
    {
        $this->rol = $rol;
    }
    public function setPuntos($puntos)
    {
        $this->puntos = $puntos;
    }
    public function setEstado($estado)
    {
        $this->estado = $estado;
    }
    public function setNuevo($nuevo)
    {
        $this->nuevo = $nuevo;
    }
}
