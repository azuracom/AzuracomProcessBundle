# Mise à jour vers 3.0.0

La version **3.0.0** supprime la dépendance à **`sylius/resource-bundle`**. Le mécanisme
« entité de base surchargeable par le projet » est conservé, mais repose désormais uniquement
sur Doctrine (mapped superclass + `resolve_target_entities`).

> ⚠️ Cette version contient des **changements cassants** (BC breaks). Aucune migration de base
> de données n'est nécessaire : les tables `process` et `process_resource_tag` sont inchangées.

## Résumé des changements cassants

1. **`sylius/resource-bundle` n'est plus requis** par le bundle.
2. **PHP >= 8.1** désormais requis (était `>=7.2`) — le bundle utilise les attributs PHP,
   `#[AsCommand]`, et les propriétés `readonly`.
3. **Modèles → mapped superclasses** : `Azuracom\ProcessBundle\Model\Process` et
   `…\Model\ProcessResourceTag` sont maintenant des `#[ORM\MappedSuperclass]` (classes de base),
   et n'implémentent plus `Sylius\Component\Resource\Model\ResourceInterface`.
4. **Nouvelles entités concrètes par défaut** : `Azuracom\ProcessBundle\Entity\Process` et
   `…\Entity\ProcessResourceTag` (`#[ORM\Entity]`). C'est l'entité par défaut quand le projet
   n'en fournit pas.
5. **Mapping Doctrine en attributs PHP** : les fichiers `config/doctrine/model/*.orm.xml` /
   `*.orm.yml` sont supprimés (le YAML n'existe plus en Doctrine ORM 3 de toute façon).
6. **Factory** : `Sylius\Component\Resource\Factory\FactoryInterface` est remplacé par
   `Azuracom\ProcessBundle\Factory\FactoryInterface` (même contrat : `createNew(): object`).
7. **Repository** : le repository par défaut est un repository Doctrine standard. Un repository
   surchargé doit étendre `Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository`
   (et non plus `Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository`).
8. **Configuration** (`azuracom_process`) : les nœuds `driver`, `resources.*.classes.controller`
   et `resources.*.classes.form` sont supprimés. `repository` et `factory` deviennent optionnels
   (valeur par défaut : `null` → le bundle fournit les services par défaut). Le nœud `model` vaut
   désormais par défaut l'**entité concrète** (`…\Entity\Process`), plus le modèle.

## Étapes de migration (projet consommateur)

### 1. Composer

Retirer `sylius/resource-bundle` du `composer.json` du projet (s'il n'était utilisé que pour ce
bundle), puis :

```bash
composer update azuracom/process-bundle sylius/resource-bundle
```

### 2. bundles.php

Retirer l'enregistrement de Sylius s'il n'est plus utilisé ailleurs :

```diff
-    Sylius\Bundle\ResourceBundle\SyliusResourceBundle::class => ['all' => true],
     Azuracom\ProcessBundle\AzuracomProcessBundle::class => ['all' => true],
```

### 3. Configuration Sylius résiduelle

Supprimer les fichiers de config propres à Sylius s'ils n'étaient là que pour ce bundle :

```bash
rm config/packages/sylius_resource.yaml
rm config/routes/sylius_resource.yaml
```

### 4. Repository surchargé

Si le projet déclare son propre repository (`azuracom_process.resources.process.classes.repository`) :

```diff
-use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
+use Azuracom\ProcessBundle\Entity\Process;
+use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
+use Doctrine\Persistence\ManagerRegistry;

-class ProcessRepository extends EntityRepository
+class ProcessRepository extends ServiceEntityRepository
 {
+    public function __construct(ManagerRegistry $registry)
+    {
+        parent::__construct($registry, Process::class);
+    }
     // …
 }
```

### 5. Injections du repository / de la factory

Remplacer les type-hints Sylius par leurs équivalents :

```diff
-use Sylius\Component\Resource\Repository\RepositoryInterface;
+use Doctrine\ORM\EntityRepository; // ou votre repository concret

-public function __construct(private RepositoryInterface $processRepository) {}
+public function __construct(private EntityRepository $processRepository) {}
```

```diff
-use Sylius\Component\Resource\Factory\FactoryInterface;
+use Azuracom\ProcessBundle\Factory\FactoryInterface;
```

### 6. Fichier `azuracom_process.yaml`

Retirer les nœuds supprimés (`driver`, `classes.controller`, `classes.form`) s'ils étaient
présents. La configuration courante reste valide :

```yaml
azuracom_process:
    user_class: App\Entity\User
    resources:
        process:
            classes:
                repository: App\Repository\ProcessRepository
```

## Surcharger l'entité Process dans un projet

Le but du bundle reste inchangé : définir ses propres champs sur une entité projet.

```php
// src/Entity/Process.php
namespace App\Entity;

use Azuracom\ProcessBundle\Model\Process as BaseProcess;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'process')]
class Process extends BaseProcess
{
    // … vos champs spécifiques au projet …
}
```

```yaml
azuracom_process:
    resources:
        process:
            classes:
                model: App\Entity\Process
```

Quand un `model` projet est configuré, le bundle **n'enregistre pas** son entité concrète par
défaut (pas de collision de table), et `resolve_target_entities` fait pointer `ProcessInterface`
vers l'entité du projet.
