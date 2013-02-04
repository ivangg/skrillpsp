
function init ()
    {
    var hiddenElements = ['paymentSelection',
                          'contactBlock',
                          'userInfoBlock',
                          'addressBlock',
                          'notMandatoryRow',
                          'spacer1',
                          'spacer2',
                          'spacer3',
                          'spacer4'];

    for (var key in hiddenElements)
        {
        var element = getElem('id', hiddenElements[key], 0);
        if (element)
            element.style.display="none";
        }
    }