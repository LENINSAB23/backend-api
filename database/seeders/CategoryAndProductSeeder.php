<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CategoryAndProductSeeder extends Seeder
{
    public function run(): void
    {
        // Limpieza completa de categorías y productos antiguos de comida/restaurante
        $foodSlugs = ['platos-tipicos', 'hamburguesas', 'bebidas', 'postres', 'frenos-suspension', 'motor-filtros'];
        $oldCategories = Category::whereIn('slug', $foodSlugs)->get();
        foreach ($oldCategories as $oldCat) {
            foreach ($oldCat->products as $oldProd) {
                OrderItem::where('product_id', $oldProd->id)->delete();
                $oldProd->delete();
            }
            $oldCat->delete();
        }

        $categories = [
            [
                'name' => 'Aceites & Lubricantes Auto/Moto',
                'slug' => 'aceites-lubricantes',
                'description' => 'Aceites sintéticos, semi-sintéticos y aditivos de alta performance para autos y motocicletas.',
                'image_url' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=500',
                'products' => [
                    [
                        'name' => 'Aceite Sintético Mobil 1 5W-30 (1 Galón)',
                        'description' => 'Protección extrema para motores a gasolina y diésel modernos. Máxima resistencia a altas temperaturas.',
                        'price' => 145.00,
                        'prep_time_minutes' => 10,
                        'image_url' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=500',
                    ],
                    [
                        'name' => 'Aceite para Moto Motul 7100 4T 10W-40 100% Sintético (1L)',
                        'description' => 'Formulado para motocicletas deportivas y urbanas de 4 tiempos. Tecnología Ester con máxima protección de caja.',
                        'price' => 58.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=500',
                    ],
                    [
                        'name' => 'Líquido de Frenos Bosch DOT 4 (500ml)',
                        'description' => 'Alto punto de ebullición para una frenada segura en carreteras de montaña y ciudad.',
                        'price' => 28.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1600793575654-910699b5e4d4?w=500',
                    ],
                    [
                        'name' => 'Refrigerante / Anti-freeze Prestone 50/50 (1 Galón)',
                        'description' => 'Protección anticorrosiva de larga duración para el sistema de radiador y enfriamiento.',
                        'price' => 42.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=500',
                    ],
                ],
            ],
            [
                'name' => 'Repuestos para Autos',
                'slug' => 'repuestos-autos',
                'description' => 'Pastillas de freno, bujías, amortiguadores y repuestos de alta gama para automóviles y SUVs.',
                'image_url' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=500',
                'products' => [
                    [
                        'name' => 'Pastillas de Freno Cerámicas Fras-le (Juego Delantero Auto)',
                        'description' => 'Frenado silencioso sin polvo negro. Compatible con Toyota Yaris, Corolla, Kia Rio y Hyundai Accent.',
                        'price' => 95.00,
                        'prep_time_minutes' => 15,
                        'image_url' => 'https://images.unsplash.com/photo-1600793575654-910699b5e4d4?w=500',
                    ],
                    [
                        'name' => 'Discos de Freno Ventilados Brembo (Par Delantero)',
                        'description' => 'Excelente disipación de calor para frenados de emergencia en ciudad y carretera.',
                        'price' => 240.00,
                        'prep_time_minutes' => 15,
                        'image_url' => 'https://images.unsplash.com/photo-1600793575654-910699b5e4d4?w=500',
                    ],
                    [
                        'name' => 'Juego de 4 Bujías de Iridio NGK Laser Iridium',
                        'description' => 'Encendido instantáneo, optimización de combustible y chispa ultra potente. Duración de hasta 100,000 km.',
                        'price' => 135.00,
                        'prep_time_minutes' => 10,
                        'image_url' => 'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=500',
                    ],
                    [
                        'name' => 'Amortiguadores a Gas Monroe OESpectrum (Par Delantero)',
                        'description' => 'Control de estabilidad superior y confort en baches y terrenos irregulares.',
                        'price' => 320.00,
                        'prep_time_minutes' => 20,
                        'image_url' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=500',
                    ],
                    [
                        'name' => 'Kit de Embrague Valeo (Disco, Plato y Collarín)',
                        'description' => 'Transmisión suave de torque, larga vida útil y resistencia al recalentamiento.',
                        'price' => 380.00,
                        'prep_time_minutes' => 25,
                        'image_url' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=500',
                    ],
                ],
            ],
            [
                'name' => 'Repuestos para Motocicletas',
                'slug' => 'repuestos-motos',
                'description' => 'Kits de arrastre, pastillas, bujías y piezas para motos lineales y mototaxis.',
                'image_url' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=500',
                'products' => [
                    [
                        'name' => 'Kit de Arrastre Reforzado Cadena con O-Ring 520 (Pulsar / FZ)',
                        'description' => 'Corona y piñón templados en acero al carbono con cadena reforzada de alta duración.',
                        'price' => 125.00,
                        'prep_time_minutes' => 15,
                        'image_url' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=500',
                    ],
                    [
                        'name' => 'Pastillas de Freno Cerámica para Moto (Delantera/Trasera)',
                        'description' => 'Frenada contundente y segura en pista seca y mojada para motos Honda, Yamaha y Bajaj.',
                        'price' => 35.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?w=500',
                    ],
                    [
                        'name' => 'Batería de Gel Sellada Yuasa YTX9-BS (12V 8Ah)',
                        'description' => 'Sellada libre de ácido líquido, a prueba de vibraciones y derrames en motos y cuatrimotos.',
                        'price' => 115.00,
                        'prep_time_minutes' => 10,
                        'image_url' => 'https://images.unsplash.com/photo-1597762127827-0242a8b34015?w=500',
                    ],
                    [
                        'name' => 'Bujía Especial para Moto NGK CPR8EA-9',
                        'description' => 'Chispa confiable para motores monocilíndricos 150cc a 250cc de 4 tiempos.',
                        'price' => 22.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=500',
                    ],
                    [
                        'name' => 'Cable de Embrague Reforzado con Forro Teflón para Moto',
                        'description' => 'Accionamiento suave y resistente a la tracción continua.',
                        'price' => 18.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=500',
                    ],
                ],
            ],
            [
                'name' => 'Baterías & Sistema Eléctrico',
                'slug' => 'baterias-electricos',
                'description' => 'Baterías de libre mantenimiento, focos LED y alternadores para autos y motos.',
                'image_url' => 'https://images.unsplash.com/photo-1597762127827-0242a8b34015?w=500',
                'products' => [
                    [
                        'name' => 'Batería Capsa Gold 13 Placas Libre Mantenimiento (Auto/Camioneta)',
                        'description' => 'Potencia de arranque superior en frío. 12V 65Ah con garantía de 18 meses.',
                        'price' => 280.00,
                        'prep_time_minutes' => 15,
                        'image_url' => 'https://images.unsplash.com/photo-1597762127827-0242a8b34015?w=500',
                    ],
                    [
                        'name' => 'Kit de Focos LED H4 Philips Ultinon Essential (Par)',
                        'description' => 'Luz blanca ultra brillante 6000K para óptima visibilidad nocturna en carretera.',
                        'price' => 160.00,
                        'prep_time_minutes' => 10,
                        'image_url' => 'https://images.unsplash.com/photo-1508974239320-0a029497e820?w=500',
                    ],
                    [
                        'name' => 'Kit de Fusibles Automotrices Surtidos (50 Unidades)',
                        'description' => 'Fusibles mini y estándar de 5A a 30A con extractor para tablero y caja de fusibles.',
                        'price' => 25.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1597762127827-0242a8b34015?w=500',
                    ],
                ],
            ],
            [
                'name' => 'Herramientas & Mecánica de Taller',
                'slug' => 'herramientas-mecanica',
                'description' => 'Escáneres de diagnóstico, gatas hidráulicas, llaves y dados para mecánica automotriz.',
                'image_url' => 'https://images.unsplash.com/photo-1508974239320-0a029497e820?w=500',
                'products' => [
                    [
                        'name' => 'Escáner Automotriz OBD2 Bluetooth Multimarca ELM327',
                        'description' => 'Lectura y borrado de códigos de falla Check Engine, sensores en vivo y telemetría por smartphone.',
                        'price' => 65.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1508974239320-0a029497e820?w=500',
                    ],
                    [
                        'name' => 'Gata Hidráulica Tipo Caimán 2.5 Toneladas con Maletín',
                        'description' => 'Estructura de acero reforzado, elevación rápida de perfil bajo para autos y camionetas.',
                        'price' => 195.00,
                        'prep_time_minutes' => 15,
                        'image_url' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=500',
                    ],
                    [
                        'name' => 'Juego de Llaves y Dados Mecánicos Cr-V 46 Piezas',
                        'description' => 'Dados de 4 a 14mm, extensión flexible y rachet con mango ergonómico antideslizante.',
                        'price' => 85.00,
                        'prep_time_minutes' => 10,
                        'image_url' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=500',
                    ],
                    [
                        'name' => 'Manómetro Medidor Digital de Presión de Llantas 150 PSI',
                        'description' => 'Pantalla LCD retroiluminada para medición exacta en autos, motos y bicicletas.',
                        'price' => 32.00,
                        'prep_time_minutes' => 5,
                        'image_url' => 'https://images.unsplash.com/photo-1600793575654-910699b5e4d4?w=500',
                    ],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $products = $catData['products'];
            unset($catData['products']);

            $category = Category::updateOrCreate(['slug' => $catData['slug']], $catData);

            foreach ($products as $prodData) {
                Product::updateOrCreate(
                    ['name' => $prodData['name'], 'category_id' => $category->id],
                    $prodData
                );
            }
        }
    }
}
