<cfcomponent>

<cfset CFC_tPath=GetCurrentTemplatePath()>

<!-------------------------------------------------------------------------->

<cffunction name="command" access="public" output="no" returntype="struct">
	<cfargument name="tCommand" type="string" required="yes">
	<cfargument name="tDomain" type="string" required="yes">
	<cfargument name="nPort" type="numeric" required="yes">
	<cfargument name="tUsername" type="string" required="yes">
	<cfargument name="tRoom" type="string" required="yes">
	<cfargument name="tSubDomain" type="string" required="yes">

	<cfargument name="tDigestURI" type="string" required="no">
	<cfargument name="tPassword" type="string" required="no">
	<cfargument name="tNC" type="string" required="no" default="00000001">

	<cfset var sReturn=structnew()>
	<cfset var aMsgOut=arraynew(1)>

	<cfset var tChallenge="">
	<cfset var tNOnce="">
	<cfset var tCNOnce=replacenocase(createuuid(),"-","","ALL")>

	<cfset var i=0>
	<cfset var j=0>
	<cfset var k=0>
	
	<cfscript>

	switch ( ARGUMENTS.tCommand ) {
	
		case 'LOGIN':
			aMsgOut=[
				{ type="INIT",		data="<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' to='#ARGUMENTS.tDomain#' version='1.0'>" },
				{ type="AUTH",		data="<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>" },
				{ type="RESPONSE",	data="username=""#ARGUMENTS.tUsername#"",realm=""#ARGUMENTS.tDomain#"",nonce=""[[nonce]]"",cnonce=""#tCNOnce#"",nc=""#ARGUMENTS.tNC#"",qop=auth,digest-uri=""#ARGUMENTS.tDigestURI#"",response=""[[challenge_response]]"",charset=utf-8" },
				{ type="",			data="<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>" },
				{ type="",			data="<stream:stream xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' to='#ARGUMENTS.tDomain#' version='1.0'>" },
				{ type="BIND",		data="<iq id='bind_1' type='set'><bind xmlns='urn:ietf:params:xml:ns:xmpp-bind'/></iq>" },
				{ type="SESSION",	data="<iq to='#ARGUMENTS.tDomain#' type='set' id='sess_1'><session xmlns='urn:ietf:params:xml:ns:xmpp-session'/></iq>" },
				{ type="JOIN",		data="<presence from='#ARGUMENTS.tUsername#@#ARGUMENTS.tDomain#' to='#ARGUMENTS.tRoom#@#ARGUMENTS.tSubDomain#.#ARGUMENTS.tDomain#/#ARGUMENTS.tUsername#'><priority>1</priority><x xmlns='http://jabber.org/protocol/muc'/></presence>" }
			];
			break;
			
		case 'LOGOUT':
			aMsgOut=[ { type="", data="<presence from='#ARGUMENTS.tUsername#@#ARGUMENTS.tDomain#' to='#ARGUMENTS.tRoom#@#ARGUMENTS.tSubDomain#.#ARGUMENTS.tDomain#/#ARGUMENTS.tUsername#' type='unavailable'/>" } ];
			break;
	}

	sReturn.tStatus="OK";
	sReturn.nStartMS=gettickcount();
	sReturn.aStep=arraynew(1);
	sReturn.aArgs=duplicate(ARGUMENTS);
	
	// INIT
	if ( isdefined("SESSION.oSocket"))
		SESSION.oSocket.close();
	
	SESSION.oSocket=createObject("java","java.net.Socket");
	SESSION.oSocket.init(ARGUMENTS.tDomain,ARGUMENTS.nPort);

	for ( i=1; i <= arraylen(aMsgOut); i ++ ) {
		sReturn.aStep[i]=structnew();
		sReturn.aStep[i].tType=aMsgOut[i].type;
		sReturn.aStep[i].tSent=write_to_socket(oSocket="#SESSION.oSocket#",tOutput="#aMsgOut[i].data#");
		sReturn.aStep[i].tReceived=read_from_socket(oSocket="#SESSION.oSocket#",nTimeoutMS="2000");
		if ( findnocase("<failure",sReturn.aStep[i].tReceived) gt 0 ) {
			sReturn.tStatus="ERROR";
			break;
		}
		if ( aMsgOut[i].type == "AUTH" ) {
			if ( findnocase("<challenge",sReturn.aStep[i].tReceived) == 0 ) {
				sReturn.tStatus="ERROR";
				break;
			} else {
				tChallenge=tostring(tobinary(listgetat(sReturn.aStep[i].tReceived,2,"><")));
				for ( j=1; j <= listlen(tChallenge); j ++ ) {
					k=listgetat(tChallenge,j);
					if ( listfirst(k,"=") == "nonce" ) {
						tNOnce=replacenocase(listlast(k,"="),"""","","ALL");
						break;
					}
				}
				aMsgOut[i+1].data=replacenocase(aMsgOut[i+1].data,"[[nonce]]",tNOnce);
				aMsgOut[i+1].data=replacenocase(aMsgOut[i+1].data,"[[challenge_response]]",build_xmpp_challenge_response(ARGUMENTS.tUsername,ARGUMENTS.tPassword,ARGUMENTS.tDomain,ARGUMENTS.tDigestURI,ARGUMENTS.tNC,tNOnce,tCNOnce));
				aMsgOut[i+1].data="<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>#tobase64(aMsgOut[i+1].data)#</response>";
			}
		}
		sReturn.aStep[i].nEndMS=gettickcount();
		if ( i == 1 )
			sReturn.aStep[i].nElapsedMS=sReturn.aStep[i].nEndMS-sReturn.nStartMS;
		else
			sReturn.aStep[i].nElapsedMS=sReturn.aStep[i].nEndMS-sReturn.aStep[i-1].nEndMS;
	}
	
	</cfscript>

	<cfreturn sReturn>
</cffunction>

<!-------------------------------------------------------------------------->

<cffunction name="build_xmpp_challenge_response" access="public" output="no" returntype="string">
	<cfargument name="tUsername" type="string" required="yes">
	<cfargument name="tPassword" type="string" required="yes">
	<cfargument name="tDomain" type="string" required="yes">
	<cfargument name="tDigestURI" type="string" required="yes">
	<cfargument name="tNC" type="string" required="yes">
	<cfargument name="tNOnce" type="string" required="yes">
	<cfargument name="tCNOnce" type="string" required="yes">

	<cfhttp url="http://localhost/psn_components/econnrefused-client/coldfusion8/build_xmpp_challenge_response.php?username=#ARGUMENTS.tUsername#&password=#ARGUMENTS.tPassword#&domain=#ARGUMENTS.tDomain#&digesturi=#ARGUMENTS.tDigestURI#&nc=#ARGUMENTS.tNC#&nonce=#ARGUMENTS.tNOnce#&cnonce=#ARGUMENTS.tCNOnce#" method="get"></cfhttp>
	
	<cfreturn CFHTTP.FileContent>
</cffunction>

<!-------------------------------------------------------------------------->

<cffunction name="read_from_socket" access="public" output="no" returntype="string">
	<cfargument name="oSocket" required="yes">
	<cfargument name="nTimeoutMS" type="numeric" required="yes">
	
	<cfset var oInputStream=ARGUMENTS.oSocket.getInputStream()>
	<cfset var tReturn="">
	<cfset var nStart=gettickcount()>
	
	<cfif isdefined("nTimeoutMS")>
		<cfloop condition="oInputStream.available() is 0 and gettickcount()-nStart lt ARGUMENTS.nTimeoutMS"></cfloop>
		<cfif oInputStream.available() is 0>
			<cfreturn "TIMEOUT">
		</cfif>
	</cfif>
	
	<cfloop condition="oInputStream.available() gt 0">
		<cfset tReturn=tReturn & chr(oInputStream.read())>
	</cfloop>

	<cfreturn tReturn>
</cffunction>

<!-------------------------------------------------------------------------->

<cffunction name="write_to_socket" access="public" output="no" returntype="string">
	<cfargument name="oSocket" required="yes">
	<cfargument name="tOutput" type="string" required="yes">
	
	<cfset var oOutputStream=ARGUMENTS.oSocket.getOutputStream()>
	<cfset oOutputStream.write(javacast("byte[]",cf_string_to_ascii_array(ARGUMENTS.tOutput)))>
	
	<cfreturn ARGUMENTS.tOutput>
</cffunction>

<!-------------------------------------------------------------------------->

<cffunction name="cf_string_to_ascii_array" access="public" output="no" returntype="array">
	<cfargument name="tString" type="string" required="yes">
	
	<cfset var i=0>
	<cfset var j=len(ARGUMENTS.tString)>
	<cfset var aReturn=arraynew(1)>
	
	<cfscript>
	
	for ( i=1; i lte j; i ++ )
		aReturn[i]=asc(mid(ARGUMENTS.tString,i,1));
	
	</cfscript>
	
	<cfreturn aReturn>
</cffunction>

<!-------------------------------------------------------------------------->

</cfcomponent>
