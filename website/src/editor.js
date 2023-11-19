// @todo dynamic import of @codemirror.
import {EditorView} from '@codemirror/view'
import {keymap, highlightSpecialChars, drawSelection,
    lineNumbers} from '@codemirror/view'
import {EditorState} from '@codemirror/state'
import {defaultHighlightStyle, syntaxHighlighting, indentOnInput, indentUnit, bracketMatching} from '@codemirror/language'
import {defaultKeymap, history, historyKeymap, indentWithTab} from '@codemirror/commands'
import {closeBrackets, closeBracketsKeymap} from '@codemirror/autocomplete'
import {php} from '@codemirror/lang-php'

export function createEditor(ref, doc, callback) {
    const startState = EditorState.create({
        doc,
        extensions: [
            lineNumbers(),
            highlightSpecialChars(),
            history(),
            drawSelection(),
            indentOnInput(),
            syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
            bracketMatching(),
            closeBrackets(),
            keymap.of([
                indentWithTab,
                ...closeBracketsKeymap,
                ...defaultKeymap,
                ...historyKeymap,
            ]),
            php(),
            EditorState.tabSize.of(4),
            indentUnit.of('\t'),
            EditorView.lineWrapping,
            EditorView.updateListener.of(update => {
                if (!update.docChanged) {
                    return;
                }
                callback(update.state.doc.toString());
            }),
        ]
    });
    return new EditorView({
        state: startState,
        parent: ref,
    })
}
