<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Парсинг галер</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 py-6 px-4">

    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <!-- Форма -->
        <form class="space-y-4" action="./parse.php" method="GET">
            <div>
                <label for="dropdown" class="block text-sm font-medium text-gray-700">Выберите опцию</label>
                <select id="dropdown" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option>Pornpics.com</option>
                </select>
                
                <input type="text" id="pages" name="pages" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Cтраниц">
                <input type="text" id="from_page" name="from_page" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Начинаем со страницы">
                <input type="text" id="limit_per_page" name="limit_per_page" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Лимит на страницу">
            </div>

            <div>
                <label for="keyword" class="block text-sm font-medium text-gray-700">Keyword</label>
                <input type="text" id="keyword" name="keyword" class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" placeholder="Кей для парсинга">
            </div>

            <div>
                <button type="submit" class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-md shadow-md hover:bg-indigo-700">Парсить</button>
            </div>
        </form>

        <div class="mt-8">
            <table class="min-w-full table-auto">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Имя файла</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Просмотреть</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(!empty($files)): ?>
                    <?php foreach($files as $filename): ?>
                    <tr class="border-t">
                        <td class="px-4 py-2 text-sm text-gray-700"><?=$filename?></td>
                        <td class="px-4 py-2 text-sm">
                            <a href="<?=$filename?>" class="text-indigo-600 hover:text-indigo-800">Скачать</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
