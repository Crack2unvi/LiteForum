document.addEventListener('DOMContentLoaded', function() {
    const toolbars = document.querySelectorAll('.bbcode-toolbar');

    toolbars.forEach(toolbar => {
        // Find the textarea that is a sibling to the toolbar's parent, or the next sibling.
        const textarea = toolbar.closest('form').querySelector('textarea');

        if (textarea) {
            toolbar.querySelectorAll('.bbcode-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    textarea.focus();

                    const tag = this.dataset.tag;
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const selectedText = textarea.value.substring(start, end);
                    let replacement = '';

                    switch (tag) {
                        case 'b':
                        case 'i':
                        case 'u':
                            replacement = `[${tag}]${selectedText}[/${tag}]`;
                            break;
                        case 'quote':
                             replacement = `[quote=KullanıcıAdı]${selectedText}[/quote]`;
                             break;
                        case 'url':
                            const url = prompt('Lütfen URL'yi girin:', 'https://');
                            if (url) {
                                replacement = `[url=${url}]${selectedText || url}[/url]`;
                            } else {
                                return; // User cancelled prompt
                            }
                            break;
                        case 'img':
                            const imgUrl = prompt('Lütfen resim URL'sini girin:', 'https://');
                            if (imgUrl) {
                                replacement = `[img]${imgUrl}[/img]`;
                            } else {
                                return; // User cancelled prompt
                            }
                            break;
                        default:
                            return;
                    }
                    
                    // Insert the new text and update selection
                    document.execCommand('insertText', false, replacement);
                });
            });
        }
    });
});
