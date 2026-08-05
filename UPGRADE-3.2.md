# Mise à jour vers 3.2.0

Les mapped superclasses `Azuracom\ProcessBundle\Model\Process` et
`…\Model\ProcessResourceTag` deviennent **`abstract`**.

## Pourquoi

Rien n'empêchait jusqu'ici de faire `new Azuracom\ProcessBundle\Model\Process()`, et Doctrine
persistait l'objet sans broncher : la mapped superclass reçoit quand même un générateur d'id, et
comme elle ne porte pas de `#[ORM\Table]`, la naming strategy déduit le nom de table de son nom
court, soit `process`, exactement la table de l'entité concrète. L'INSERT partait donc au bon
endroit.

En revanche `Gedmo\Mapping\ExtensionMetadataFactory::getExtensionMetadata()` commence par
`if ($meta->isMappedSuperclass) { return []; }` : les attributs `#[Gedmo\Timestampable]` du modèle
ne sont lus qu'en repartant d'une **entité concrète**, dont Gedmo remonte ensuite les
`class_parents()`. Résultat : des lignes insérées avec `created_at` / `updated_at` à `NULL`, sans
aucune erreur. Le `abstract` transforme ce piège silencieux en erreur fatale immédiate.

## Impact

Aucun, sauf si votre code instancie directement une classe de `Model\` - auquel cas il souffrait
déjà du bug ci-dessus. Les type-hints sur `Model\Process` restent valides, seul le `new` est
désormais interdit.

## Que faire

Instancier l'entité concrète :

```diff
-use Azuracom\ProcessBundle\Model\Process;
+use Azuracom\ProcessBundle\Entity\Process;
 ...
 $process = new Process(MonHandler::class);
```

Ou, mieux, passer par la factory, qui construit la classe déclarée dans
`azuracom_process.resources.process.classes.model` et renseigne l'utilisateur connecté :

```php
use Azuracom\ProcessBundle\Factory\FactoryInterface;

public function import(FactoryInterface $processFactory)
{
    $process = $processFactory->createNew();
}
```

Si un projet a déjà des lignes créées par l'ancien chemin, leur `created_at` est à `NULL` : prévoir
un backfill (par exemple depuis `updated_at` ou `started_at`) si un tri chronologique s'appuie
dessus.
