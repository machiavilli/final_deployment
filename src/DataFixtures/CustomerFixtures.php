<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rows = json_decode('[{"ref":"customer_121","ref_username":"customer_roberto_santiago","name":"Roberto Santiago","email":"roberto.santiago@email.com","customer_name":"Roberto Santiago","phone":"+639123456789","username":"roberto_santiago","created_by":null},{"ref":"customer_122","ref_username":"customer_elena_magbanua","name":"Elena Magbanua","email":"elena.magbanua@email.com","customer_name":"Elena Magbanua","phone":"+639234567890","username":"elena_magbanua","created_by":null},{"ref":"customer_123","ref_username":"customer_carlos_reyes","name":"Carlos Reyes","email":"carlos.reyes@email.com","customer_name":"Carlos Reyes","phone":"+639345678901","username":"carlos_reyes","created_by":null},{"ref":"customer_124","ref_username":"customer_maria_lim","name":"Maria Lim","email":"maria.lim@email.com","customer_name":"Maria Lim","phone":"+639456789012","username":"maria_lim","created_by":null},{"ref":"customer_125","ref_username":"customer_antonio_santos","name":"Antonio Santos","email":"antonio.santos@email.com","customer_name":"Antonio Santos","phone":"+639567890123","username":"antonio_santos","created_by":null},{"ref":"customer_126","ref_username":"customer_carmela_garcia","name":"Carmela Garcia","email":"carmela.garcia@email.com","customer_name":"Carmela Garcia","phone":"+639678901234","username":"carmela_garcia","created_by":null},{"ref":"customer_127","ref_username":"customer_jose_villanueva","name":"Jose Villanueva","email":"jose.villanueva@email.com","customer_name":"Jose Villanueva","phone":"+639789012345","username":"jose_villanueva","created_by":null},{"ref":"customer_128","ref_username":"customer_ana_ocampo","name":"Ana Ocampo","email":"ana.ocampo@email.com","customer_name":"Ana Ocampo","phone":"+639890123456","username":"ana_ocampo","created_by":null},{"ref":"customer_129","ref_username":"customer_manuel_tan","name":"Manuel Tan","email":"manuel.tan@email.com","customer_name":"Manuel Tan","phone":"+639901234567","username":"manuel_tan","created_by":null},{"ref":"customer_130","ref_username":"customer_patricia_mendoza","name":"Patricia Mendoza","email":"patricia.mendoza@email.com","customer_name":"Patricia Mendoza","phone":"+639012345678","username":"patricia_mendoza","created_by":null},{"ref":"customer_131","ref_username":"customer_francisco_cruz","name":"Francisco Cruz","email":"francisco.cruz@email.com","customer_name":"Francisco Cruz","phone":"+639112233445","username":"francisco_cruz","created_by":null},{"ref":"customer_132","ref_username":"customer_linda_dela_rosa","name":"Linda Dela Rosa","email":"linda.delarosa@email.com","customer_name":"Linda Dela Rosa","phone":"+639223344556","username":"linda_dela_rosa","created_by":null},{"ref":"customer_133","ref_username":"customer_ricardo_reyes_jr_","name":"Ricardo Reyes Jr.","email":"ricardo.reyes@email.com","customer_name":"Ricardo Reyes Jr.","phone":"+639334455667","username":"ricardo_reyes_jr_","created_by":null},{"ref":"customer_134","ref_username":"customer_sofia_lim","name":"Sofia Lim","email":"sofia.lim@email.com","customer_name":"Sofia Lim","phone":"+639445566778","username":"sofia_lim","created_by":null},{"ref":"customer_135","ref_username":"customer_miguel_santos","name":"Miguel Santos","email":"miguel.santos@email.com","customer_name":"Miguel Santos","phone":"+639556677889","username":"miguel_santos","created_by":null},{"ref":"customer_136","ref_username":"customer_ju1","name":"juanne","email":"juwon@gmail.com","customer_name":"juanne","phone":"53939","username":"ju1","created_by":null}]', true, 512, JSON_THROW_ON_ERROR);

        foreach ($rows as $row) {
            $customer = new Customer();
            $customer->setName($row['name']);
            $customer->setEmail($row['email']);
            $customer->setCustomerName($row['customer_name']);
            $customer->setPhone($row['phone']);
            $customer->setUsername($row['username']);

            if ($row['created_by'] !== null && $this->hasReference('user_' . $row['created_by'], User::class)) {
                $customer->setCreatedBy($this->getReference('user_' . $row['created_by'], User::class));
            }

            $manager->persist($customer);
            $this->addReference($row['ref'], $customer);
            $this->addReference($row['ref_username'], $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}