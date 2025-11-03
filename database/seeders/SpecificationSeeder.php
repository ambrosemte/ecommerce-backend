<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategorySpecification;
use App\Models\SpecificationKey;
use App\Models\SpecificationValue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $foodSpecification = [
            "Type" => ["type" => "list", "data" => ["Fresh", "Packaged", "Frozen", "Organic", "Vegan", "Non-Vegan"]],
            "Cuisine" => ["type" => "list", "data" => ["Italian", "Indian", "Chinese", "Continental", "Local", "Nigerian"]],
            "Expire" => ["type" => "text", "data" => []],
            "Weight" => ["type" => "integer", "data" => []],
            "Packaging Type" => ["type" => "list", "data" => ["Box", "Can", "Pouch", "Bottle"]],
            "Allergens" => ["type" => "list", "data" => ["Gluten-Free", "Nut-Free", "Dairy-Free", "Soy-Free"]],
            "Calories (Per Service)" => ["type" => "list", "data" => []],
        ];


        $giftSpecification = [
            "Gift Type" => ["type" => "list", "data" => ["Personal", "Corporate", "Seasonal", "DIY"]],
            "Occasion" => ["type" => "list", "data" => ["Birthday", "Anniversary", "Christmas", "Valentine\'s Day"]],
            "Recipient" => ["type" => "list", "data" => ["Male", "Female", "Unisex", "Kids"]],
            "Packaging" => ["type" => "list", "data" => ["Gift Wrapped", "Boxed", "Bagged"]],
            "Theme" => ["type" => "list", "data" => ["Romantic", "Professional", "Casual"]],
        ];

        $fashionSpecification = [
            "Clothing Type" => ["type" => "list", "data" => ["Shirt", "Pants", "Dress", "Jacket", "Shirt"]],
            "Size" => ["type" => "list", "data" => ["XS", "S", "M", "L", "XL", "XXL"]],
            "Material" => ["type" => "list", "data" => ["Cotton", "Polyster", "Silk", "Denim", "Leather"]],
            "Color" => ["type" => "list", "data" => ["Red", "Blue", "Black", "White", "Multi-Color"]],
            "Brand" => ["type" => "list", "data" => ["Nike", "Adidas", "Gucci", "Zara", "Tommy Hilfiger"]],
            "Gender" => ["type" => "list", "data" => ["Male", "Female", "Unisex"]],
            "Season" => ["type" => "list", "data" => ["Summer", "Winter", "Fall", "Spring"]],
            "Pattern" => ["type" => "list", "data" => ["Plain", "Striped", "Checked", "Floral", "Polka Dots"]],
        ];

        $gadgetSpecification = [
            "Device Type" => ["type" => "list", "data" => ["Smartphone", "Laptop", "Tablet", "Smartwatch", "Camera"]],
            "Brand" => ["type" => "list", "data" => ["Apple", "Samsung", "Dell", "HP", "Sony", "Canon"]],
            "Model Name/Number" => ["type" => "text", "data" => []],
            "Operating System" => ["type" => "list", "data" => ["Android", "iOS", "Windows", "macOS", "Linux"]],
            "Screen Size" => ["type" => "integer", "data" => []],
            "Battery Capacity" => ["type" => "integer", "data" => []],
            "RAM" => ["type" => "integer", "data" => []],
            "Storage" => ["type" => "integer", "data" => []],
            "Connectivity" => ["type" => "multiple", "data" => ["Wi-Fi", "Bluetooth", "NFC", "5G"]],
            "Features" => ["type" => "multiple", "data" => ["Waterproof", "Dual Camera", "FIngerprint Sensor"]]

        ];

        $accessorySpecification = [
            "Accessory Type" => ["type" => "list", "data" => ["Watch", "Bag", "Wallet", "Jewelry", "Sunglasses", "Belt"]],
            "Brand" => ["type" => "list", "data" => ["Fossil", "Ray-Ban", "Guess", "Montblanc"]],
            "Material" => ["type" => "list", "data" => ["Leather", "Metal", "Plastic", "Fabric", "Gold", "Silver"]],
            "Color" => ["type" => "list", "data" => ["Black", "Brown", "White", "Gold", "Rose Gold", "Transparent"]],
            "Gender" => ["type" => "list", "data" => ["Male", "Female", "Unisex"]],
            "Size" => ["type" => "list", "data" => ["Small", "Medium", "Large", "Adjustable"]],
            "Features" => ["type" => "multiple", "data" => ["Water-Resistant", "Lightweight", "Foldable"]],
            "Style" => ["type" => "list", "data" => ["Casual", "Formal", "Sporty", "Luxury"]]
        ];

        $allSpecifications = [
            'Food' => $foodSpecification,
            'Gift' => $giftSpecification,
            'Fashion' => $fashionSpecification,
            'Gadget' => $gadgetSpecification,
            'Accessory' => $accessorySpecification,
        ];

        foreach ($allSpecifications as $categoryName => $specifications) {
            $category = Category::where('name', $categoryName)->first();

            if (!$category) {
                continue;
            }

            $categoryId = $category->id;

            foreach ($specifications as $key => $value) {
                $specificationKey = SpecificationKey::firstOrCreate([
                    'name' => $key,
                    'type' => $value['type']
                ]);

                CategorySpecification::firstOrCreate([
                    'category_id' => $categoryId,
                    'specification_key_id' => $specificationKey->id
                ]);

                foreach ($value['data'] as $item) {
                    $specificationKey->specificationValues()->firstOrCreate([
                        'value' => $item
                    ]);
                }
            }
        }
    }
}
