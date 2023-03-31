var title, poster, tags, bbcode, cid, bookFile;

function handleMultipleResults(items)
{
	var rows = [];
	$.each(items, function(key, val)
	{
		var id = val.ISBN;
		var idstring = val.ISBN;
		if(id.length == 0)
		{
			id = val.ASIN;
			idstring = "ASIN: " + val.ASIN;
		}
		
		rows.push('<tr id="' + key + '"><td><button id="' + id + '" onclick="return search(this.id);">Use</button></td><td>' + val.Title + ' - ' + val.Author + ' (' + val.PublicationDate + ' - ' + id + ')</td></tr>');
	});
	return rows;
}

function search(keyword)
{
	$.getJSON("book-uploader.php?action=search&keyword="+keyword, function(data)
	{
		var $Item = data.Items[0];
		$(".my-new-list,.use").remove();

		cid = $('select[name="cid"]').val(), title = $Item.Title, poster = $Item.ImgurImage, tags = $Item.TagString, bbcode = $Item.BBCode;

		$('<div id="use"><p><b>Title:</b> '+title+'</p><p><b>Poster:</b><br><img src="'+poster+'" style="max-width: 100px;"></p><p><b>Tags:</b><br />'+tags+'</p><p><b>BBCODE: [<a href="#" id="parseBBCODE">parse</a>]</b><br><textarea style="width: 800px; height: 400px; border: 1px solid red;" class="s" id="bbcode">'+bbcode+'</textarea></p></div>').insertBefore("#bookUploadTable");

		$("#createTorrent").fadeIn("slow");
	});
}

$('select[name="book"]').change(function(e)
{
	var $this = $(this), bookFile = $this.val(), keyword = bookFile.replace(".pdf", "");

	$("#bookUploadTable").prevUntil("table").remove();
	
	if(keyword)
	{
		$(".my-new-list").remove();
		$.getJSON("book-uploader.php?action=search&keyword="+keyword, function(data)
		{
			if(!data.Items.length)
			{
				alert("Nothing Found!");
			}
			else
			{
				var $Output = handleMultipleResults(data.Items);
				$("<table/>",
				{
					"class": "my-new-list",
					html: $Output.join("")
				}).insertBefore("#bookUploadTable");
			}
		});
	}
	else
	{
		$(".my-new-list").remove();
	}
});

$('#parseBBCODE').live('click', function(e)
{
	e.preventDefault();

	$("#parsedBBCODE").remove();
	
	$.ajax
	({
		url: "book-uploader.php",
		data: "action=parseBBCODE&bbcode="+$.TSUE.urlEncode(bbcode),
		success: function(response)
		{
			$('<div id="parsedBBCODE">'+response+'</div>').insertAfter("#use");
		}
	});

	return false;
});

$('#createTorrent').live('click', function(e)
{
	e.preventDefault();

	cid = $('select[name="cid"]').val(), bookFile = $('select[name="book"]').val();

	if(!cid)
	{
		alert("Please select a category!");
		return false;
	}

	var buildQuery = "action=createTorrent&cid="+parseInt(cid)+"&bookFile="+$.TSUE.urlEncode(bookFile)+"&title="+$.TSUE.urlEncode(title)+"&poster="+$.TSUE.urlEncode(poster)+"&tags="+$.TSUE.urlEncode(tags)+"&bbcode="+$.TSUE.urlEncode(bbcode);

	$.ajax
	({
		url: "book-uploader.php",
		data: buildQuery,
		success: function(response)
		{
			alert(response)
		}
	});

	return false;
});