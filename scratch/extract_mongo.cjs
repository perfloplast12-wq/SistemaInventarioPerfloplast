const { MongoClient } = require('mongodb');
const fs = require('fs');

async function main() {
    const uri = "mongodb+srv://alexestuardo642_db_user:aJAQr5Szf5nuBHMI@cluster0.ewhwqsh.mongodb.net/perflo-plast?retryWrites=true&w=majority&appName=Cluster0";
    const client = new MongoClient(uri);

    try {
        await client.connect();
        const database = client.db('perflo-plast');
        
        // Obtener productos
        const products = await database.collection('products').find({}).toArray();
        console.log(`Found ${products.length} products.`);
        
        // Obtener ajustes
        const settings = await database.collection('settings').find({}).toArray();
        console.log(`Found ${settings.length} settings.`);

        const data = {
            products: products,
            settings: settings
        };

        fs.writeFileSync('mongo_dump.json', JSON.stringify(data, null, 2));
        console.log('Data exported to mongo_dump.json');
    } finally {
        await client.close();
    }
}

main().catch(console.error);
