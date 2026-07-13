import { NextRequest, NextResponse } from "next/server"
import { jwtHelper } from "@workspace/common"

const ACCESS_TOKEN_COOKIE = "access_token"

export default function proxy(request: NextRequest) {
  const token = request.cookies.get(ACCESS_TOKEN_COOKIE)?.value
  const loginUrl = new URL("/login", request.url)

  if (!token || !jwtHelper.isValid(token)) {
    return NextResponse.redirect(loginUrl)
  }

  const roles = jwtHelper.getProperty<string[]>(token, "roles")

  if (!Array.isArray(roles) || !roles.includes("admin")) {
    loginUrl.searchParams.set("forbidden", "true")
    return NextResponse.redirect(loginUrl)
  }

  return NextResponse.next()
}

export const config = {
  matcher: ["/dashboard/:path*"],
}
