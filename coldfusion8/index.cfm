<cfoutput>

</head>

<body>

<cfinvoke component="xmpp_chat_bot" method="command" returnvariable="sResponse"
	tCommand="LOGIN"
	tRoom="web"
	tSubDomain="conference"
	tDomain="econnrefused.com"
	nPort="5222"
	tDigestURI="xmpp/econnrefused.com"
	tUsername="psnbot"
	tPassword="motmot"
>

<cfdump var="#sResponse#">

<cfinvoke component="xmpp_chat_bot" method="command" returnvariable="sResponse"
	tCommand="LOGOUT"
	tRoom="web"
	tSubDomain="conference"
	tDomain="econnrefused.com"
	nPort="5222"
	tUsername="psnbot"
>

<cfdump var="#sResponse#">

</cfoutput>
