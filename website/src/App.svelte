<script>
    import {createEditor} from "./editor.js";
    import {onMount} from "svelte";

    let editor;
    onMount(() => {
        const resultMatch = window.location.pathname.match(/^\/r\/([0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})$/i);
        if (resultMatch !== null) {
            fetchResult(resultMatch[1]);
        }
        createEditor(editor, data.code, update => data.code = update)
    })

    const apiUrl = 'https://gkyhj54sul.execute-api.us-east-1.amazonaws.com/prod';
    const data = {
        code: `<?php\n\nmodule_load_include('inc', 'foo', 'node.admin');\n\n\\Drupal::moduleHandler()->loadInclude('node', 'inc', 'node.admin');`,
        level: '9',
        strictRules: false,
        bleedingEdge: false,
        treatPhpDocTypesAsCertain: true
    };
    let processing = false;
    let result = JSON.parse(`{
  "tabs": [
    {
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ],
      "title": "PHP 8.1 – 8.3 (2 errors)"
    }
  ],
  "versionedErrors": [
    {
      "phpVersion": 80100,
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ]
    },
    {
      "phpVersion": 80200,
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ]
    },
    {
      "phpVersion": 80300,
      "errors": [
        {
          "line": 3,
          "message": "Call to deprecated function module_load_include():\\nin drupal:9.4.0 and is removed from drupal:11.0.0.\\n  Use \\\\Drupal::moduleHandler()->loadInclude($module, $type, $name = NULL).\\n  Note that including code from uninstalled extensions is no longer\\n  supported.",
          "ignorable": true
        },
        {
          "line": 3,
          "message": "File node.admin.inc could not be loaded from module_load_include because foo module is not found.",
          "ignorable": true
        }
      ]
    }
  ],
  "id": "d22c810f-dd6c-4769-aa4d-41c3be5792f4"
}
`);
    async function fetchResult(id) {
        result = null;
        processing = true;
        try {
            const response = await fetch(`${apiUrl}/result?id=${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            result = await response.json();
            data.code = result.code;
            data.level = result.level;
            data.strictRules = result.strictRules;
            data.bleedingEdge = result.bleedingEdge;
            data.treatPhpDocTypesAsCertain = result.treatPhpDocTypesAsCertain;
        } catch (error) {
            console.error(error);
        } finally {
            processing = false;
        }
    }
    async function analyse (event) {
        event.preventDefault();
        await doAnalyse(false);
    }

    async function doAnalyse(saveResult) {
        result = null;
        processing = true;
        try {
            const response = await fetch(`${apiUrl}/analyse`, {
                method: 'POST',
                body: JSON.stringify({
                    ...data,
                    saveResult
                }),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            result = await response.json();
        } catch (error) {
            console.error(error);
        } finally {
            processing = false;
        }
    }

    async function share() {
        const id = result?.id;
        if (!id) {
            await doAnalyse(true);
            window.history.replaceState({}, '', '/r/' + result.id);
        }
        if (typeof window.navigator.share !== 'undefined') {
            await window.navigator.share({url: window.location.href});
        } else if (typeof window.navigator.clipboard !== 'undefined') {
            await window.navigator.clipboard.writeText(window.location.href);
        }
    }
</script>
<svelte:head>
    <!-- Fathom - beautiful, simple website analytics -->
    <script src="https://cdn.usefathom.com/script.js" data-site="JQIHBWMK" defer></script>
    <!-- / Fathom -->
</svelte:head>
<div class="py-8">
    <main class="max-w-5xl md:px-6 mx-auto bg-white rounded-lg">
        <div class="p-8">
            <h1 class="text-3xl">Playground</h1>
            <p class="mb-4 md:px-0 pt-4 px-4">Try out PHPStan with phpstan-drupal and all of its features here in the editor. <a href="https://phpstan.org/" class="hover:no-underline underline">Learn more about PHPStan »</a></p>
            <form class="space-y-4" on:submit={analyse}>
                <div bind:this={editor}></div>
                {#if !editor}
                    <textarea bind:value={data.code} rows="10" name="code" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-light-navy-blue sm:text-sm sm:leading-6"></textarea>
                {/if}
                <details class="border border-gray-300 rounded-md p-2">
                    <summary class="text-sm">Advanced options</summary>
                    <div class="flex flex-col items-center md:flex-row mt-4 space-x-6">
                        <label class="inline-flex items-center md:mt-0 mt-4">
                            <input type="checkbox" name="strictRules" bind:checked={data.strictRules} class="border-gray-300 rounded">
                            <span class="ml-2">Strict rules</span>
                        </label>
                        <label class="inline-flex items-center md:mt-0 mt-4">
                            <input type="checkbox" name="bleedingEdge" bind:checked={data.bleedingEdge} class="border-gray-300 rounded">
                            <span class="ml-2">Bleeding edge</span>
                        </label>
                        <label class="inline-flex items-center md:mt-0 mt-4">
                            <input type="checkbox" name="treatPhpDocTypesAsCertain" bind:checked={data.treatPhpDocTypesAsCertain} class="border-gray-300 rounded">
                            <span class="ml-2">Treat PHPDoc types as certain</span>
                        </label>
                    </div>
                </details>
                <div class="flex flex-col items-center md:flex-row mt-4 space-x-4">
                    <select name="level" bind:value={data.level} class="block border border-gray-300 md:mt-0 md:mx-0 mt-4 mx-4 rounded-md">
                        <option value="0">Level 0</option>
                        <option value="1">Level 1</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5</option>
                        <option value="6">Level 6</option>
                        <option value="7">Level 7</option>
                        <option value="8">Level 8</option>
                        <option value="9">Level 9</option>
                    </select>
                    <button disabled={processing} class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" type="submit">Analyse</button>
                    <div class="flex-grow"></div>
                    <button on:click={share} type="button" data-bind="click: share" class="bg-gray-100 border border-gray-300 flex-grow font-medium h-10 hover:bg-gray-200 inline-flex items-center justify-center leading-4 md:flex-grow-0 md:mx-0 md:w-32 mx-4 px-2.5 py-3 rounded-lg text-md w-auto">
                        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="h-6 w-6"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                        <span class="ml-2" data-bind="text: shareText">Share</span>
                    </button>
                </div>
            </form>
            {#if processing}
                <div class="m-auto w-12 h-12 mt-8">
                    <div role="status">
                        <svg aria-hidden="true" class="text-gray-200 animate-spin dark:text-gray-600 fill-blue-700" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                        </svg>
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            {/if}
            <div class="pt-4">
                {#each result?.tabs || [] as tab}
                    {#if tab.errors.length > 0}
                        <div class="flex items-stretch md:mx-0 mx-4">
                            <span class="bg-red-100 flex-grow font-medium h-12 inline-flex items-center justify-center leading-4 md:flex-grow-0 mt-4 px-4 py-3 rounded-lg text-lg text-red-900" data-bind="text: errorsText">
                                Found {tab.errors.length} errors
                            </span>
                        </div>
                        <table class="mt-8 w-full">
                            <thead>
                                <tr>
                                    <th class="bg-gray-50 border-b border-gray-200 font-medium leading-4 px-6 py-3 text-base text-gray-500 text-left tracking-wider"> Line </th>
                                    <th class="bg-gray-50 border-b border-gray-200 font-medium leading-4 px-6 py-3 text-base text-gray-500 text-left tracking-wider"> Error </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                            {#each tab.errors as error}
                                <tr>
                                    <td class="border-b border-gray-200 font-medium leading-5 px-6 py-4 text-base text-gray-500">
                                        {error.line}
                                    </td>
                                    <td class="border-b border-gray-200 font-medium leading-5 px-6 py-4 text-base text-gray-500">
                                        <div class="flex flex-col md:flex-row md:items-center">
                                            <div class="flex-shrink">
                                                <div>{error.message}</div>
                                                {#if error.tip}
                                                    <div>{error.tip}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                            </tbody>
                        </table>
                    {/if}
                {/each}
            </div>
        </div>
    </main>
</div>
