<?php
class User {
    public ?int $id;
    public ?string $email;
    public ?string $mot_de_passe;
    public ?string $nom;
    public ?DateTime $reset_token_expires;
    public ?string $reset_token_hash;
    public ?int $tel;
    const TABLE_NAME = 'Utilisateur';
}